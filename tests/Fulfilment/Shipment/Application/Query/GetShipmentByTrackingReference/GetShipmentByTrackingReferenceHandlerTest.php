<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\GetShipmentByTrackingReference;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Query\GetShipmentByTrackingReference\GetShipmentByTrackingReference;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetShipmentByTrackingReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAShipmentByItsTrackingReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->dispatched()->tracked('ACME-4Q7X2K9')->store();
        ShipmentTestFactory::new()->dispatched()->tracked('ACME-OTHER')->store();

        // When
        $result = $this->ask(new GetShipmentByTrackingReference('ACME-4Q7X2K9'));

        // Then
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame($shipment->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->createdAt);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFailsWhenNoShipmentCarriesThatReference(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->ask(new GetShipmentByTrackingReference('ACME-NEVER-ISSUED'));
    }
}
