<?php

declare(strict_types=1);

namespace Shipping\Tests\Shipment\Domain;

use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Shipping\Shipment\Domain\Event\ShipmentCreated;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Event\ShipmentDispatched;
use Shipping\Shipment\Domain\Exception\InvalidShipmentTransitionException;
use Shipping\Shipment\Domain\Shipment;
use Shipping\Shipment\Domain\ShipmentId;

final class ShipmentTest extends AggregateRootTestCase
{
    #[Test]
    public function itIsCreatedForAnOrder(): void
    {
        $id = ShipmentId::generate();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given()
            ->when(static fn () => Shipment::create($id, 'order-1', $createdAt))
            ->then(new ShipmentCreated($id->toString(), 'order-1', $createdAt->format('c')));
    }

    #[Test]
    public function itDispatchesAPendingShipment(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ShipmentCreated($id, 'order-1', $createdAt->format('c')))
            ->when(static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt))
            ->then(new ShipmentDispatched($id, $dispatchedAt->format('c')));
    }

    #[Test]
    public function itCannotBeDeliveredBeforeBeingDispatched(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new ShipmentCreated($id, 'order-1', $createdAt->format('c')))
            ->when(static fn (Shipment $shipment) => $shipment->markDelivered(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(InvalidShipmentTransitionException::class);
    }

    #[Test]
    public function itIsDeliveredOnceDispatched(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
        $deliveredAt = new \DateTimeImmutable('2026-01-03T00:00:00+00:00');

        $this
            ->given(
                new ShipmentCreated($id, 'order-1', $createdAt->format('c')),
                new ShipmentDispatched($id, $dispatchedAt->format('c')),
            )
            ->when(static fn (Shipment $shipment) => $shipment->markDelivered($deliveredAt))
            ->then(new ShipmentDelivered($id, $deliveredAt->format('c')));
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }
}
