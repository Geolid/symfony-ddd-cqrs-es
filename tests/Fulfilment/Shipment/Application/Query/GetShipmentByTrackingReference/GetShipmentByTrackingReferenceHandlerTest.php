<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\GetShipmentByTrackingReference;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Query\GetShipmentByTrackingReference\GetShipmentByTrackingReference;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetShipmentByTrackingReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByTrackingReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->create();
        $this->store($shipment);
        $this->store(ShipmentTestFactory::new()->prepared()->manifested('ACME-OTHER')->dispatched()->create());

        // When
        $result = $this->ask(new GetShipmentByTrackingReference('ACME-4Q7X2K9'));

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($shipment->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
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
        $this->ask(new GetShipmentByTrackingReference('ACME-NEVER-ISSUED'));
    }
}
