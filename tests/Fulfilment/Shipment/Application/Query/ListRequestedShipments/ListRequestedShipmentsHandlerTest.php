<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\ListRequestedShipments;

use Fulfilment\Shipment\Application\Query\ListRequestedShipments\ListRequestedShipments;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListRequestedShipmentsHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsRequestedShipments(): void
    {
        // Given
        $requested = ShipmentTestFactory::new()->store();
        ShipmentTestFactory::new()->prepared()->many(2)->store();

        // When
        $results = iterator_to_array($this->ask(new ListRequestedShipments()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($requested->id()->toString(), $results[0]->id);
        self::assertSame($requested->orderId(), $results[0]->orderId);
    }
}
