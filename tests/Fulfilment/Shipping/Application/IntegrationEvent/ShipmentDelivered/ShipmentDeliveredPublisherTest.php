<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\IntegrationEvent\ShipmentDelivered;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentDeliveredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentDeliveredIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['reference'], $event->reference);
        self::assertSame(
            $builder['deliveredAt']->format(\DateTimeInterface::ATOM),
            $event->deliveredAt->format(\DateTimeInterface::ATOM),
        );
    }
}
