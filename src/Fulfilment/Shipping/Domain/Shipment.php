<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain;

use Fulfilment\Shipping\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipping\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipping\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipping\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipping\Domain\Event\ShipmentManifested;
use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentDirection;
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
use Shared\Domain\ValueObject\PostalAddress;

#[Aggregate('fulfilment.shipping.shipment')]
final class Shipment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ShipmentState>> */
    private const array TRANSITIONS = [
        ShipmentState::REQUESTED->value => [ShipmentState::PREPARED, ShipmentState::CANCELLED],
        ShipmentState::PREPARED->value => [ShipmentState::MANIFESTED, ShipmentState::CANCELLED],
        ShipmentState::MANIFESTED->value => [ShipmentState::DISPATCHED],
        ShipmentState::DISPATCHED->value => [ShipmentState::DELIVERED],
        ShipmentState::DELIVERED->value => [],
        ShipmentState::CANCELLED->value => [],
    ];

    #[Id]
    public private(set) ShipmentId $id;
    public private(set) string $reference;
    public private(set) ShipmentDirection $direction;
    public private(set) string $buyerId;
    public private(set) PostalAddress $origin;
    public private(set) PostalAddress $destination;
    private ?TrackingNumber $trackingNumber = null;
    private ShipmentState $state;

    public static function request(
        ShipmentId $id,
        string $reference,
        ShipmentDirection $direction,
        string $buyerId,
        PostalAddress $origin,
        PostalAddress $destination,
        \DateTimeImmutable $createdAt,
    ): self {
        $self = new self();
        $self->recordThat(new ShipmentRequested(
            id: $id->toString(),
            reference: $reference,
            direction: $direction,
            buyerId: $buyerId,
            origin: $origin,
            destination: $destination,
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

    /**
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     */
    public function manifest(TrackingNumber $trackingNumber, \DateTimeImmutable $manifestedAt): void
    {
        if ($this->state->isManifested()) {
            \assert(null !== $this->trackingNumber);

            if ($this->trackingNumber->equals($trackingNumber)) {
                return;
            }

            throw ShipmentAlreadyTrackedException::forReference($this->id, $this->trackingNumber->value);
        }

        if (!new CanTransitionToSpecification(self::TRANSITIONS, ShipmentState::MANIFESTED)->isSatisfiedBy($this->state)) {
            throw ShipmentInvalidTransitionException::cannotManifest($this->id, $this->state);
        }

        $this->recordThat(new ShipmentManifested(
            id: $this->id->toString(),
            trackingNumber: $trackingNumber,
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
            throw ShipmentInvalidTransitionException::cannotDispatch($this->id, $this->state);
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
            throw ShipmentInvalidTransitionException::cannotDeliver($this->id, $this->state);
        }

        $this->recordThat(new ShipmentDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt,
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

    #[Apply]
    private function applyRequested(ShipmentRequested $event): void
    {
        $this->id = ShipmentId::fromString($event->id);
        $this->reference = $event->reference;
        $this->direction = $event->direction;
        $this->buyerId = $event->buyerId;
        $this->origin = $event->origin;
        $this->destination = $event->destination;
        $this->trackingNumber = null;
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
        $this->trackingNumber = $event->trackingNumber;
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
}
