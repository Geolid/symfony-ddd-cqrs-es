<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentReturnRejected;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnRejected\ShipmentReturnRejectedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentReturnRejectedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnRejected('item damaged beyond resale')->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentReturnRejectedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('item damaged beyond resale', $event->reason);
    }
}
