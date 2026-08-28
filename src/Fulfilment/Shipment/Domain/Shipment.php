<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain;

use Fulfilment\Shipment\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipment\Domain\Event\ShipmentRequested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnApproved;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnReceived;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRejected;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRequested;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Specification\CanTransitionToSpecification;
use Fulfilment\Shipment\Domain\Specification\HasReachedSpecification;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('fulfilment.shipment.shipment')]
final class Shipment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ShipmentState>> */
    private const array TRANSITIONS = [
        ShipmentState::REQUESTED->value => [ShipmentState::PREPARED, ShipmentState::CANCELLED],
        ShipmentState::PREPARED->value => [ShipmentState::MANIFESTED, ShipmentState::CANCELLED],
        ShipmentState::CANCELLED->value => [],
        ShipmentState::MANIFESTED->value => [ShipmentState::DISPATCHED],
        ShipmentState::DISPATCHED->value => [ShipmentState::DELIVERED],
        ShipmentState::DELIVERED->value => [ShipmentState::RETURN_REQUESTED],
        ShipmentState::RETURN_REQUESTED->value => [ShipmentState::RETURN_MANIFESTED],
        ShipmentState::RETURN_MANIFESTED->value => [ShipmentState::RETURN_DISPATCHED],
        ShipmentState::RETURN_DISPATCHED->value => [ShipmentState::RETURN_RECEIVED],
        ShipmentState::RETURN_RECEIVED->value => [ShipmentState::RETURN_APPROVED, ShipmentState::RETURN_REJECTED],
        ShipmentState::RETURN_APPROVED->value => [],
        ShipmentState::RETURN_REJECTED->value => [],
    ];

    #[Id]
    public private(set) ShipmentId $id;
    public private(set) string $orderId;
    public private(set) string $customerId;
    public private(set) PostalAddress $shippingAddress;
    private ?TrackingReference $trackingReference = null;
    private ?TrackingReference $returnTrackingReference = null;
    private ShipmentState $state;

    public static function request(
        ShipmentId $id,
        string $orderId,
        string $customerId,
        PostalAddress $shippingAddress,
        \DateTimeImmutable $createdAt,
    ): self {
        $self = new self();
        $self->recordThat(new ShipmentRequested(
            id: $id->toString(),
            orderId: $orderId,
            customerId: $customerId,
            shippingAddress: [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
                'countryCode' => $shippingAddress->address->countryCode->value,
            ],
            createdAt: $createdAt,
        ));

        return $self;
    }

    public function prepare(\DateTimeImmutable $preparedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::PREPARED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new ShipmentPrepared(
            id: $this->id->toString(),
            preparedAt: $preparedAt,
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::CANCELLED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::CANCELLED)->isSatisfiedBy($this->state)) {
            $this->recordThat(new ShipmentCancellationRejected(
                id: $this->id->toString(),
                state: $this->state,
                rejectedAt: $cancelledAt,
            ));

            return;
        }

        $this->recordThat(new ShipmentCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    /**
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     */
    public function manifest(TrackingReference $trackingReference, \DateTimeImmutable $manifestedAt): void
    {
        if ($this->state->isManifested()) {
            \assert(null !== $this->trackingReference);

            if ($this->trackingReference->equals($trackingReference)) {
                return;
            }

            throw ShipmentAlreadyTrackedException::forReference($this->trackingReference->value);
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::MANIFESTED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotManifest($this->state);
        }

        $this->recordThat(new ShipmentManifested(
            id: $this->id->toString(),
            trackingReference: $trackingReference->value,
            manifestedAt: $manifestedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::DISPATCHED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::DISPATCHED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotDispatch($this->state);
        }

        $this->recordThat(new ShipmentDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function deliver(\DateTimeImmutable $deliveredAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::DELIVERED)->isSatisfiedBy($this->state)) {
            return;
        }

        // Tolerates skipping DISPATCHED — a missed carrier transit scan still delivers.
        if (!new HasReachedSpecification(self::TRANSITIONS, ShipmentState::MANIFESTED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotDeliver($this->state);
        }

        $this->recordThat(new ShipmentDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt,
        ));
    }

    public function requestReturn(\DateTimeImmutable $requestedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::RETURN_REQUESTED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new ShipmentReturnRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    /**
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     */
    public function manifestReturn(TrackingReference $returnTrackingReference, \DateTimeImmutable $manifestedAt): void
    {
        if ($this->state->isReturnManifested()) {
            \assert(null !== $this->returnTrackingReference);

            if ($this->returnTrackingReference->equals($returnTrackingReference)) {
                return;
            }

            throw ShipmentAlreadyTrackedException::forReference($this->returnTrackingReference->value);
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::RETURN_MANIFESTED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotManifestReturn($this->state);
        }

        $this->recordThat(new ShipmentReturnManifested(
            id: $this->id->toString(),
            returnTrackingReference: $returnTrackingReference->value,
            manifestedAt: $manifestedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function dispatchReturn(\DateTimeImmutable $dispatchedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::RETURN_DISPATCHED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::RETURN_DISPATCHED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotDispatchReturn($this->state);
        }

        $this->recordThat(new ShipmentReturnDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function receiveReturn(\DateTimeImmutable $receivedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::RETURN_RECEIVED)->isSatisfiedBy($this->state)) {
            return;
        }

        // Tolerates skipping RETURN_DISPATCHED — a missed carrier transit scan still receives.
        if (!new HasReachedSpecification(self::TRANSITIONS, ShipmentState::RETURN_MANIFESTED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotReceiveReturn($this->state);
        }

        $this->recordThat(new ShipmentReturnReceived(
            id: $this->id->toString(),
            receivedAt: $receivedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function approveReturn(\DateTimeImmutable $approvedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::RETURN_APPROVED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::RETURN_APPROVED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotApproveReturn($this->state);
        }

        $this->recordThat(new ShipmentReturnApproved(
            id: $this->id->toString(),
            approvedAt: $approvedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function rejectReturn(string $reason, \DateTimeImmutable $rejectedAt): void
    {
        if (new HasReachedSpecification(self::TRANSITIONS, ShipmentState::RETURN_REJECTED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::RETURN_REJECTED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotRejectReturn($this->state);
        }

        $this->recordThat(new ShipmentReturnRejected(
            id: $this->id->toString(),
            reason: $reason,
            rejectedAt: $rejectedAt,
        ));
    }

    #[Apply]
    private function applyRequested(ShipmentRequested $event): void
    {
        $this->id = ShipmentId::fromString($event->id);
        $this->orderId = $event->orderId;
        $this->customerId = $event->customerId;
        $this->shippingAddress = PostalAddress::of(
            FullName::of($event->shippingAddress['firstName'], $event->shippingAddress['lastName']),
            Address::of($event->shippingAddress['street'], $event->shippingAddress['postalCode'], $event->shippingAddress['city'], $event->shippingAddress['countryCode']),
        );
        $this->trackingReference = null;
        $this->returnTrackingReference = null;
        $this->state = ShipmentState::REQUESTED;
    }

    #[Apply]
    private function applyPrepared(ShipmentPrepared $event): void
    {
        $this->state = ShipmentState::PREPARED;
    }

    #[Apply]
    private function applyManifested(ShipmentManifested $event): void
    {
        $this->trackingReference = TrackingReference::fromString($event->trackingReference);
        $this->state = ShipmentState::MANIFESTED;
    }

    #[Apply]
    private function applyDispatched(ShipmentDispatched $event): void
    {
        $this->state = ShipmentState::DISPATCHED;
    }

    #[Apply]
    private function applyDelivered(ShipmentDelivered $event): void
    {
        $this->state = ShipmentState::DELIVERED;
    }

    #[Apply]
    private function applyCancelled(ShipmentCancelled $event): void
    {
        $this->state = ShipmentState::CANCELLED;
    }

    #[Apply]
    private function applyCancellationRejected(ShipmentCancellationRejected $event): void
    {
    }

    #[Apply]
    private function applyReturnRequested(ShipmentReturnRequested $event): void
    {
        $this->state = ShipmentState::RETURN_REQUESTED;
    }

    #[Apply]
    private function applyReturnManifested(ShipmentReturnManifested $event): void
    {
        $this->returnTrackingReference = TrackingReference::fromString($event->returnTrackingReference);
        $this->state = ShipmentState::RETURN_MANIFESTED;
    }

    #[Apply]
    private function applyReturnDispatched(ShipmentReturnDispatched $event): void
    {
        $this->state = ShipmentState::RETURN_DISPATCHED;
    }

    #[Apply]
    private function applyReturnReceived(ShipmentReturnReceived $event): void
    {
        $this->state = ShipmentState::RETURN_RECEIVED;
    }

    #[Apply]
    private function applyReturnApproved(ShipmentReturnApproved $event): void
    {
        $this->state = ShipmentState::RETURN_APPROVED;
    }

    #[Apply]
    private function applyReturnRejected(ShipmentReturnRejected $event): void
    {
        $this->state = ShipmentState::RETURN_REJECTED;
    }
}
