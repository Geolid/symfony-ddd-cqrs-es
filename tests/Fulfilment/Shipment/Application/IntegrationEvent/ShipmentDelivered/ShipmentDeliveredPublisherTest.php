<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentDelivered;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentDeliveredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentBuilder::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentDeliveredIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
    }
}
