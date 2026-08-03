<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\GetShipmentByOrder;

use Fulfilment\Shipment\Application\Query\GetShipmentByOrder\GetShipmentByOrder;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class GetShipmentByOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAShipmentByItsOrder(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();
        $this->store($shipment);

        // When
        $result = $this->ask(new GetShipmentByOrder($shipment->orderId()));

        // Then
        self::assertNotNull($result);
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
    }

    #[Test]
    public function itReturnsNullWhenNoShipmentExistsForTheOrder(): void
    {
        // When
        $result = $this->ask(new GetShipmentByOrder(Uuid::uuid7()->toString()));

        // Then
        self::assertNull($result);
    }
}
