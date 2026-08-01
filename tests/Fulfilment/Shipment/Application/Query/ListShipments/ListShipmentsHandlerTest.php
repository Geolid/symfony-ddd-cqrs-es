<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\ListShipments;

use Fulfilment\Shipment\Application\Query\ListShipments\ListShipments;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListShipmentsHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsShipments(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // When
        $result = $this->ask(new ListShipments());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($shipment->id()->toString(), $result->items[0]->id);
        self::assertSame(1, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }

    #[Test]
    public function itListsShipmentsByStatus(): void
    {
        // Given
        $dispatched = ShipmentTestFactory::new()->dispatched()->create();
        $this->store($dispatched);
        $this->store(ShipmentTestFactory::new()->create());

        // When
        $result = $this->ask(new ListShipments(status: 'dispatched'));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($dispatched->id()->toString(), $result->items[0]->id);
        self::assertSame(1, $result->pagination->totalItems);
    }
}
