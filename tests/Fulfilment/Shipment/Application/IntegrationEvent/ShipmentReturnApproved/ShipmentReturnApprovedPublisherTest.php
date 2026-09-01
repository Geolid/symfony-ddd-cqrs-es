<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentReturnApproved;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnApproved\ShipmentReturnApprovedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentReturnApprovedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentBuilder::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnApproved()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentReturnApprovedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }
}
