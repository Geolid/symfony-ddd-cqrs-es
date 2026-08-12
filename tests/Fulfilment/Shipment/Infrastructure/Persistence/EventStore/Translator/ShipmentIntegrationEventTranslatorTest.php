<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\EventStore\Translator;

use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentTrackingReferenceAssignedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Support\AbstractIntegrationTestCase;

final class ShipmentIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesTheDispatchOnShipmentDispatched(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->dispatched()->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesTheDeliveryOnShipmentDelivered(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->dispatched()->delivered()->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(2, $published);
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $published[0]);
        $event = $published[1];
        self::assertInstanceOf(ShipmentDeliveredIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesTheTrackingReferenceOnTrackingReferenceAssigned(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->dispatched()->tracked('ACME-4Q7X2K9')->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(2, $published);
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $published[0]);
        $event = $published[1];
        self::assertInstanceOf(ShipmentTrackingReferenceAssignedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('ACME-4Q7X2K9', $event->trackingReference);
    }
}
