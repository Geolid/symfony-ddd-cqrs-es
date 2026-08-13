<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\ListPendingShipments;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
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
        $pending = ShipmentTestFactory::new()->store();
        ShipmentTestFactory::new()->dispatched()->many(2)->store();
        ShipmentTestFactory::new()->dispatched()->delivered()->many(2)->store();

        // When
        $results = iterator_to_array($this->ask(new ListPendingShipments()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($pending->id()->toString(), $results[0]->id);
        self::assertSame($pending->orderId(), $results[0]->orderId);
        self::assertSame(ShipmentStatus::PENDING, $results[0]->status);
        self::assertNull($results[0]->trackingReference);
        self::assertNotNull($results[0]->createdAt);
        self::assertNull($results[0]->dispatchedAt);
        self::assertNull($results[0]->deliveredAt);
    }
}
