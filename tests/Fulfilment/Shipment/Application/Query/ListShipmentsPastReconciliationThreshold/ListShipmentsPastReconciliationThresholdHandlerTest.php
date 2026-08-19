<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold;

use Fulfilment\Shipment\Application\Query\ListShipmentsPastReconciliationThreshold\ListShipmentsPastReconciliationThreshold;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\MockClock;

final class ListShipmentsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-10T00:00:00+00:00'));
        $stuck = ShipmentTestFactory::new()->prepared()
            ->manifested(manifestedAt: new \DateTimeImmutable('2026-02-07T00:00:00+00:00'))
            ->store();
        ShipmentTestFactory::new()->prepared()
            ->manifested(manifestedAt: new \DateTimeImmutable('2026-02-09T12:00:00+00:00'))
            ->store();
        ShipmentTestFactory::new()->prepared()
            ->manifested(manifestedAt: new \DateTimeImmutable('2026-02-07T00:00:00+00:00'))
            ->dispatched()
            ->store();

        // When
        $results = iterator_to_array($this->ask(new ListShipmentsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
