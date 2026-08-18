<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\GetShipmentByReturnTrackingReference;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Query\GetShipmentByReturnTrackingReference\GetShipmentByReturnTrackingReference;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetShipmentByReturnTrackingReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByReturnTrackingReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-4Q7X2K9')
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested('ACME-RETURN-1')
            ->store();
        ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-OTHER')
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested('ACME-RETURN-OTHER')
            ->store();

        // When
        $result = $this->ask(new GetShipmentByReturnTrackingReference('ACME-RETURN-1'));

        // Then
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame($shipment->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $result->status);
        self::assertSame('ACME-RETURN-1', $result->returnTrackingReference);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->ask(new GetShipmentByReturnTrackingReference('ACME-RETURN-NEVER-ISSUED'));
    }
}
