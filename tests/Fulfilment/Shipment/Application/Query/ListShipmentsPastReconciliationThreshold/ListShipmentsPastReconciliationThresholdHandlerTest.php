<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold;

use Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThreshold;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListShipmentsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $stuck = ShipmentTestFactory::new()->prepared()
            ->manifested(manifestedAt: $now->modify('-3 days'))
            ->create();
        $this->store($stuck);
        $this->store(ShipmentTestFactory::new()->prepared()
            ->manifested(manifestedAt: $now->modify('-12 hours'))
            ->create());
        $this->store(ShipmentTestFactory::new()->prepared()
            ->manifested(manifestedAt: $now->modify('-3 days'))
            ->dispatched()
            ->create());

        // When
        $results = iterator_to_array($this->ask(new ListShipmentsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
