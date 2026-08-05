<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\EventStore\Translator;

use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentTrackingReferenceAssignedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ShipmentIntegrationEventTranslatorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishesTheDispatchOnShipmentDispatched(): void
    {
        // When
        $shipment = ShipmentTestFactory::new()->withOrderId('order-1')->dispatched()->create();
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(\sprintf('fulfilment.shipment.integration.%s', $shipment->id()->toString()));
        self::assertCount(1, $published);
        $event = $published[0];
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame('order-1', $event->orderId);
    }

    #[Test]
    public function itPublishesTheDeliveryOnShipmentDelivered(): void
    {
        // When
        $shipment = ShipmentTestFactory::new()->withOrderId('order-1')->delivered()->create();
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(\sprintf('fulfilment.shipment.integration.%s', $shipment->id()->toString()));
        self::assertCount(2, $published);
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $published[0]);
        $event = $published[1];
        self::assertInstanceOf(ShipmentDeliveredIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame('order-1', $event->orderId);
    }

    #[Test]
    public function itPublishesTheTrackingReferenceOnTrackingReferenceAssigned(): void
    {
        // When
        $shipment = ShipmentTestFactory::new()->withOrderId('order-1')->tracked('ACME-4Q7X2K9')->create();
        $this->store($shipment);

        // Then
        $published = $this->publishedTo(\sprintf('fulfilment.shipment.integration.%s', $shipment->id()->toString()));
        self::assertCount(2, $published);
        self::assertInstanceOf(ShipmentDispatchedIntegrationEvent::class, $published[0]);
        $event = $published[1];
        self::assertInstanceOf(ShipmentTrackingReferenceAssignedIntegrationEvent::class, $event);
        self::assertSame($shipment->id()->toString(), $event->shipmentId);
        self::assertSame('order-1', $event->orderId);
        self::assertSame('ACME-4Q7X2K9', $event->trackingReference);
    }

    /**
     * @return list<object>
     */
    private function publishedTo(string $streamId): array
    {
        $published = [];

        foreach ($this->service(Store::class)->load(new Criteria(new StreamCriterion($streamId))) as $message) {
            $published[] = $message->event();
        }

        return $published;
    }
}
