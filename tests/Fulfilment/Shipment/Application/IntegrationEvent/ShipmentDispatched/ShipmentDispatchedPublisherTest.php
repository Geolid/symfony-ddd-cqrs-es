<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentDispatched;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentDispatchedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentDispatchedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['reference'], $event->reference);
        self::assertSame(
            $builder['dispatchedAt']->format(\DateTimeInterface::ATOM),
            $event->dispatchedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
