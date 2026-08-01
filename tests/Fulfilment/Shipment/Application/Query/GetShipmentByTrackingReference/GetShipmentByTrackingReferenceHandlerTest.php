<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\GetShipmentByTrackingReference;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Query\GetShipmentByTrackingReference\GetShipmentByTrackingReference;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetShipmentByTrackingReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAShipmentByItsCarrierReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();
        $this->store($shipment);
        $this->store(ShipmentTestFactory::new()->tracked('ACME-OTHER')->create());

        // When
        $result = $this->ask(new GetShipmentByTrackingReference('ACME-4Q7X2K9'));

        // Then
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
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
