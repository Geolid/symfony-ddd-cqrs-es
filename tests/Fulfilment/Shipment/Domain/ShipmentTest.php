<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain;

use Fulfilment\Shipment\Domain\Event\ShipmentCreated;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\TrackingReferenceAssigned;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Patchlevel\EventSourcing\PhpUnit\Test\AggregateRootTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;

final class ShipmentTest extends AggregateRootTestCase
{
    #[Test]
    public function itIsCreatedForAnOrder(): void
    {
        $id = ShipmentId::generate();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();

        $this
            ->given()
            ->when(static fn () => Shipment::create($id, $orderId, $customerId, 'buyer@example.com', $createdAt))
            ->then(new ShipmentCreated($id->toString(), $orderId, $customerId, 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itDispatchesAPendingShipment(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(new ShipmentCreated($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Shipment $shipment) => $shipment->dispatch($dispatchedAt))
            ->then(new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itIsTrackedOnceHandedToTheCarrier(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new ShipmentCreated($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)),
                new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->assignTrackingReference(TrackingReference::fromString('ACME-4Q7X2K9')))
            ->then(new TrackingReferenceAssigned($id, 'ACME-4Q7X2K9'));
    }

    #[Test]
    public function itCannotBeTrackedBeforeBeingDispatched(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new ShipmentCreated($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Shipment $shipment) => $shipment->assignTrackingReference(TrackingReference::fromString('ACME-4Q7X2K9')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    #[Test]
    public function itCannotBeTrackedTwice(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $dispatchedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');

        $this
            ->given(
                new ShipmentCreated($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)),
                new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
                new TrackingReferenceAssigned($id, 'ACME-4Q7X2K9'),
            )
            ->when(static fn (Shipment $shipment) => $shipment->assignTrackingReference(TrackingReference::fromString('ACME-OTHER')))
            ->expectsException(ShipmentInvalidTransitionException::class);
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
                new ShipmentCreated($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)),
                new ShipmentDispatched($id, $dispatchedAt->format(\DateTimeInterface::ATOM)),
            )
            ->when(static fn (Shipment $shipment) => $shipment->markDelivered($deliveredAt))
            ->then(new ShipmentDelivered($id, $deliveredAt->format(\DateTimeInterface::ATOM)));
    }

    #[Test]
    public function itCannotBeDeliveredBeforeBeingDispatched(): void
    {
        $id = ShipmentId::generate()->toString();
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $this
            ->given(new ShipmentCreated($id, Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 'buyer@example.com', $createdAt->format(\DateTimeInterface::ATOM)))
            ->when(static fn (Shipment $shipment) => $shipment->markDelivered(new \DateTimeImmutable('2026-01-02T00:00:00+00:00')))
            ->expectsException(ShipmentInvalidTransitionException::class);
    }

    protected function aggregateClass(): string
    {
        return Shipment::class;
    }
}
