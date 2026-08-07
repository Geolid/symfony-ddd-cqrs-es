<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\ListPendingShipments;

use Fulfilment\Shipment\Application\Query\ListPendingShipments\ListPendingShipments;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListPendingShipmentsHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsOnlyPendingShipments(): void
    {
        // Given
        $pending = ShipmentTestFactory::new()->create();
        $this->store($pending);
        $this->store(ShipmentTestFactory::new()->dispatched()->create());

        // When
        $results = iterator_to_array($this->ask(new ListPendingShipments()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($pending->id()->toString(), $results[0]->id);
    }
}
