<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\IntegrationEvent\ShipmentDelivered;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentDeliveredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->manifested()->dispatched()->delivered()->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentDeliveredIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($shipment->orderId, $event->orderId);
        self::assertSame(
            $shipment->deliveredAt?->format(\DateTimeInterface::ATOM),
            $event->deliveredAt?->format(\DateTimeInterface::ATOM),
        );
    }
}
