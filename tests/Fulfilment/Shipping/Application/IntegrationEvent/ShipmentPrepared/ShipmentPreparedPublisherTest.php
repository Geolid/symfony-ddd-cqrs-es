<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\IntegrationEvent\ShipmentPrepared;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared\ShipmentPreparedIntegrationEvent;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentPreparedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentPreparedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['sourceId'], $event->sourceId);
        self::assertSame($builder['preparedAt']->format(\DateTimeInterface::ATOM), $event->preparedAt->format(\DateTimeInterface::ATOM));
    }
}
