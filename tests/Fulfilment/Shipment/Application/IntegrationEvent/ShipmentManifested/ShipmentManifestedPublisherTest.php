<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentManifested;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentManifested\ShipmentManifestedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
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
        self::assertSame($builder['reference'], $event->reference);
        self::assertSame($builder['trackingNumber']->value, $event->trackingNumber);
    }
}
