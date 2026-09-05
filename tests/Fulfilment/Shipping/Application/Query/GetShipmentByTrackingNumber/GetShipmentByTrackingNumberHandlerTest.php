<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Query\GetShipmentByTrackingNumber;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Query\GetShipmentByTrackingNumber\GetShipmentByTrackingNumber;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetShipmentByTrackingNumberHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $other = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $shipment = $builder->create();
        $this->store($other, $shipment);

        // When
        $result = $this->ask(new GetShipmentByTrackingNumber($builder['trackingNumber']->value));

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($builder['reference'], $result->reference);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($builder['trackingNumber']->value, $result->trackingNumber);
        self::assertNotNull($result->createdAt);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->ask(new GetShipmentByTrackingNumber(ShipmentBuilder::sample('trackingNumber')->value));
    }
}
