<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\IntegrationEvent\ShipmentManifested;

use Fulfilment\Shipping\Application\IntegrationEvent\ShipmentManifested\ShipmentManifestedIntegrationEvent;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentManifestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared()->manifested();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentManifestedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['sourceId'], $event->sourceId);
        self::assertSame($builder['trackingNumber']->value, $event->trackingNumber);
    }
}
