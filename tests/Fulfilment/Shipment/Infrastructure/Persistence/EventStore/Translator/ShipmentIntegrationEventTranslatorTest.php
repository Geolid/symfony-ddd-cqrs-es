<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\EventStore\Translator;

use Fulfilment\Shipment\Application\Event\ShipmentCancelledIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentManifestedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentReturnApprovedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentReturnRejectedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Support\AbstractIntegrationTestCase;

final class ShipmentIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesTheTrackingReferenceOnShipmentManifested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested('ACME-4Q7X2K9')->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(ShipmentManifestedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('ACME-4Q7X2K9', $event->trackingReference);
    }

    #[Test]
    public function itPublishesTheDispatchOnShipmentDispatched(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(2, $published);
        self::assertInstanceOf(ShipmentManifestedIntegrationEvent::class, $published[0]);
        $event = $published[1];
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesTheDeliveryOnShipmentDelivered(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(3, $published);
        self::assertInstanceOf(ShipmentManifestedIntegrationEvent::class, $published[0]);
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $published[1]);
        $event = $published[2];
        self::assertInstanceOf(ShipmentDeliveredIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesTheCancellationOnShipmentCancelled(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->cancelled()->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(ShipmentCancelledIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesTheReturnApprovalOnShipmentReturnApproved(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnApproved()->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(4, $published);
        $event = $published[3];
        self::assertInstanceOf(ShipmentReturnApprovedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }

    #[Test]
    public function itPublishesTheReturnRejectionOnShipmentReturnRejected(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnRejected('item damaged beyond resale')->create();

        // When
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(IntegrationStreamId::build('fulfilment.shipment', $shipment->id()->toString()));
        self::assertCount(4, $published);
        $event = $published[3];
        self::assertInstanceOf(ShipmentReturnRejectedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('item damaged beyond resale', $event->reason);
    }
}
