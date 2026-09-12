<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain;

use Fulfilment\Shipping\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipping\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipping\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipping\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipping\Domain\Event\ShipmentErased;
use Fulfilment\Shipping\Domain\Event\ShipmentErasureApproved;
use Fulfilment\Shipping\Domain\Event\ShipmentManifested;
use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;
use Shared\Domain\Specification\HasReachedSpecification;
use Shared\Domain\ValueObject\ErasureState;
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('fulfilment.shipping.shipment')]
final class Shipment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ShipmentState>> */
    private const array OPERATIONAL_TRANSITIONS = [
        ShipmentState::REQUESTED->value => [ShipmentState::PREPARED, ShipmentState::CANCELLED],
        ShipmentState::PREPARED->value => [ShipmentState::MANIFESTED, ShipmentState::CANCELLED],
        ShipmentState::MANIFESTED->value => [ShipmentState::DISPATCHED],
        ShipmentState::DISPATCHED->value => [ShipmentState::DELIVERED],
        ShipmentState::DELIVERED->value => [],
        ShipmentState::CANCELLED->value => [],
    ];

    /** @var array<string, list<ErasureState>> */
    private const array ERASURE_TRANSITIONS = [
        ErasureState::RETAINED->value => [ErasureState::APPROVED],
        ErasureState::APPROVED->value => [ErasureState::ERASED],
        ErasureState::ERASED->value => [],
    ];

    #[Id]
    public private(set) ShipmentId $id;
    public private(set) string $orderId;
    public private(set) PostalAddress $origin;
    public private(set) PostalAddress $destination;
    private ?TrackingNumber $trackingNumber = null;
    private ShipmentState $operationalState;
    private ErasureState $erasureState;

    public static function request(
        ShipmentId $id,
        string $orderId,
        string $shopperId,
        PostalAddress $origin,
        PostalAddress $destination,
        \DateTimeImmutable $createdAt,
    ): self {
        $self = new self();
        $self->recordThat(new ShipmentRequested(
            id: $id,
            orderId: $orderId,
            shopperId: $shopperId,
            origin: $origin,
            destination: $destination,
            createdAt: $createdAt,
        ));

        return $self;
    }

    public function prepare(\DateTimeImmutable $preparedAt): void
    {
        if (!$this->canTransitionOperationalTo(ShipmentState::PREPARED)) {
            return;
        }

        $this->recordThat(new ShipmentPrepared(
            id: $this->id,
            preparedAt: $preparedAt,
        ));
    }

    /**
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     */
    public function manifest(TrackingNumber $trackingNumber, \DateTimeImmutable $manifestedAt): void
    {
        if ($this->operationalState->isManifested()) {
            \assert(null !== $this->trackingNumber);

            if ($this->trackingNumber->equals($trackingNumber)) {
                return;
            }

            throw ShipmentAlreadyTrackedException::forReference($this->id, $this->trackingNumber->value);
        }

        if (!$this->canTransitionOperationalTo(ShipmentState::MANIFESTED)) {
            throw ShipmentInvalidTransitionException::cannotManifest($this->id, $this->operationalState);
        }

        $this->recordThat(new ShipmentManifested(
            id: $this->id,
            trackingNumber: $trackingNumber,
            manifestedAt: $manifestedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if ($this->hasReachedOperational(ShipmentState::DISPATCHED)) {
            return;
        }

        if (!$this->canTransitionOperationalTo(ShipmentState::DISPATCHED)) {
            throw ShipmentInvalidTransitionException::cannotDispatch($this->id, $this->operationalState);
        }

        $this->recordThat(new ShipmentDispatched(
            id: $this->id,
            dispatchedAt: $dispatchedAt,
        ));
    }

    /**
     * @throws ShipmentInvalidTransitionException
     */
    public function deliver(\DateTimeImmutable $deliveredAt): void
    {
        if ($this->hasReachedOperational(ShipmentState::DELIVERED)) {
            return;
        }

        // Tolerates skipping DISPATCHED — a missed carrier transit scan still delivers.
        if (!$this->hasReachedOperational(ShipmentState::MANIFESTED)) {
            throw ShipmentInvalidTransitionException::cannotDeliver($this->id, $this->operationalState);
        }

        $this->recordThat(new ShipmentDelivered(
            id: $this->id,
            deliveredAt: $deliveredAt,
        ));

        $this->tryErase($deliveredAt);
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if ($this->hasReachedOperational(ShipmentState::CANCELLED)) {
            return;
        }

        if (!$this->canTransitionOperationalTo(ShipmentState::CANCELLED)) {
            $this->recordThat(new ShipmentCancellationRejected(
                id: $this->id,
                state: $this->operationalState,
                rejectedAt: $cancelledAt,
            ));

            return;
        }

        $this->recordThat(new ShipmentCancelled(
            id: $this->id,
            cancelledAt: $cancelledAt,
        ));

        $this->tryErase($cancelledAt);
    }

    public function approveErasure(\DateTimeImmutable $approvedAt): void
    {
        if (!$this->canTransitionErasureTo(ErasureState::APPROVED)) {
            return;
        }

        $this->recordThat(new ShipmentErasureApproved(
            id: $this->id,
            approvedAt: $approvedAt,
        ));

        $this->tryErase($approvedAt);
    }

    private function tryErase(\DateTimeImmutable $at): void
    {
        if (!$this->canErase()) {
            return;
        }

        $this->recordThat(new ShipmentErased(
            id: $this->id,
            erasedAt: $at,
        ));
    }

    private function canErase(): bool
    {
        return $this->erasureState->isApproved() && ($this->operationalState->isDelivered() || $this->operationalState->isCancelled());
    }

    private function canTransitionOperationalTo(ShipmentState $target): bool
    {
        return new CanTransitionToSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    private function hasReachedOperational(ShipmentState $target): bool
    {
        return new HasReachedSpecification(self::OPERATIONAL_TRANSITIONS, $target)->isSatisfiedBy($this->operationalState);
    }

    private function canTransitionErasureTo(ErasureState $target): bool
    {
        return new CanTransitionToSpecification(self::ERASURE_TRANSITIONS, $target)->isSatisfiedBy($this->erasureState);
    }

    #[Apply]
    private function applyRequested(ShipmentRequested $event): void
    {
        $this->id = $event->id;
        $this->orderId = $event->orderId;
        $this->origin = $event->origin;
        $this->destination = $event->destination;
        $this->trackingNumber = null;
        $this->operationalState = ShipmentState::REQUESTED;
        $this->erasureState = ErasureState::RETAINED;
    }

    #[Apply]
    private function applyPrepared(ShipmentPrepared $event): void
    {
        $this->operationalState = ShipmentState::PREPARED;
    }

    #[Apply]
    private function applyManifested(ShipmentManifested $event): void
    {
        $this->trackingNumber = $event->trackingNumber;
        $this->operationalState = ShipmentState::MANIFESTED;
    }

    #[Apply]
    private function applyDispatched(ShipmentDispatched $event): void
    {
        $this->operationalState = ShipmentState::DISPATCHED;
    }

    #[Apply]
    private function applyDelivered(ShipmentDelivered $event): void
    {
        $this->operationalState = ShipmentState::DELIVERED;
    }

    #[Apply]
    private function applyCancelled(ShipmentCancelled $event): void
    {
        $this->operationalState = ShipmentState::CANCELLED;
    }

    #[Apply]
    private function applyCancellationRejected(ShipmentCancellationRejected $event): void
    {
    }

    #[Apply]
    private function applyErasureApproved(ShipmentErasureApproved $event): void
    {
        $this->erasureState = ErasureState::APPROVED;
    }

    #[Apply]
    private function applyErased(ShipmentErased $event): void
    {
        $this->erasureState = ErasureState::ERASED;
    }
}
