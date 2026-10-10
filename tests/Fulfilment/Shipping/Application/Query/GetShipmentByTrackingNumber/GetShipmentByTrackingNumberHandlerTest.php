<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Query\GetShipmentByTrackingNumber;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Query\GetShipmentByTrackingNumber\GetShipmentByTrackingNumber;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Fulfilment\Tests\Shipping\Support\Factory\TrackingNumberFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetShipmentByTrackingNumberHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $other = ShipmentFactory::new()->prepared()->manifested()->dispatched()->create();
        $trackingNumber = TrackingNumberFactory::new()->create();
        $shipment = ShipmentFactory::new()->prepared()->manifested($trackingNumber)->dispatched()->create();
        $this->store($other, $shipment);

        // When
        $result = $this->ask(new GetShipmentByTrackingNumber($trackingNumber->value));

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($shipment->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($trackingNumber->value, $result->trackingNumber);
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
        $this->ask(new GetShipmentByTrackingNumber(TrackingNumberFactory::new()->create()->value));
    }
}
