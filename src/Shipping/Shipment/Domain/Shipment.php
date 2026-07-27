<?php

declare(strict_types=1);

namespace Shipping\Shipment\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shipping\Shipment\Domain\Event\ShipmentCreated;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Event\ShipmentDispatched;
use Shipping\Shipment\Domain\Exception\InvalidShipmentTransitionException;

#[Aggregate('shipping.shipment')]
final class Shipment implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private ShipmentId $id;
    private ShipmentStatus $status;

    public function id(): ShipmentId
    {
        return $this->id;
    }

    public static function create(ShipmentId $id, string $orderId, \DateTimeImmutable $createdAt): self
    {
        $self = new self();
        $self->recordThat(new ShipmentCreated(
            id: $id->toString(),
            orderId: $orderId,
            createdAt: $createdAt->format('c'),
        ));

        return $self;
    }

    /**
     * @throws InvalidShipmentTransitionException
     */
    public function dispatch(\DateTimeImmutable $dispatchedAt): void
    {
        if (ShipmentStatus::PENDING !== $this->status) {
            throw InvalidShipmentTransitionException::forId($this->id, $this->status, 'dispatch');
        }

        $this->recordThat(new ShipmentDispatched(
            id: $this->id->toString(),
            dispatchedAt: $dispatchedAt->format('c'),
        ));
    }

    /**
     * @throws InvalidShipmentTransitionException
     */
    public function markDelivered(\DateTimeImmutable $deliveredAt): void
    {
        if (ShipmentStatus::DISPATCHED !== $this->status) {
            throw InvalidShipmentTransitionException::forId($this->id, $this->status, 'markDelivered');
        }

        $this->recordThat(new ShipmentDelivered(
            id: $this->id->toString(),
            deliveredAt: $deliveredAt->format('c'),
        ));
    }

    #[Apply]
    private function applyShipmentCreated(ShipmentCreated $event): void
    {
        $this->id = ShipmentId::fromString($event->id);
        $this->status = ShipmentStatus::PENDING;
    }

    #[Apply]
    private function applyShipmentDispatched(ShipmentDispatched $event): void
    {
        $this->status = ShipmentStatus::DISPATCHED;
    }

    #[Apply]
    private function applyShipmentDelivered(ShipmentDelivered $event): void
    {
        $this->status = ShipmentStatus::DELIVERED;
    }
}
