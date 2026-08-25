<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentDispatched;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentDispatchedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
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
}
