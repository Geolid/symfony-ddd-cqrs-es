<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\IntegrationEvent\ShipmentCancelled;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentCancelled\ShipmentCancelledIntegrationEvent;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentCancelledPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->cancelled();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentCancelledIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['reference'], $event->reference);
        self::assertSame(
            $builder['cancelledAt']->format(\DateTimeInterface::ATOM),
            $event->cancelledAt->format(\DateTimeInterface::ATOM),
        );
    }
}
