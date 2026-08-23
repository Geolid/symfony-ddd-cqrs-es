<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\EventStore\Publisher;

use Fulfilment\Shipment\Application\Event\ShipmentCancelledIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentManifestedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentReturnApprovedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentReturnRejectedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesOnShipmentManifested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested('ACME-4Q7X2K9')->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOfType(ShipmentManifestedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('ACME-4Q7X2K9', $event->trackingReference);
    }

    #[Test]
    public function itPublishesOnShipmentDispatched(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOfType(ShipmentDispatchedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesOnShipmentDelivered(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOfType(ShipmentDeliveredIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesOnShipmentCancelled(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->cancelled()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOfType(ShipmentCancelledIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesOnShipmentReturnApproved(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnApproved()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOfType(ShipmentReturnApprovedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesOnShipmentReturnRejected(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnRejected('item damaged beyond resale')->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOfType(ShipmentReturnRejectedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('item damaged beyond resale', $event->reason);
    }
}
