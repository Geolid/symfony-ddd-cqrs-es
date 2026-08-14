<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrdersWithExpiredBillingRetention;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrdersWithExpiredBillingRetention\ListOrdersWithExpiredBillingRetention;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ListOrdersWithExpiredBillingRetentionHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsOrdersPlacedBeforeTheGivenCutoff(): void
    {
        // Given
        $expired = OrderTestFactory::new()->withPlacedAt(new \DateTimeImmutable('2010-01-01T00:00:00+00:00'))->store();
        OrderTestFactory::new()->withPlacedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->store();

        // When
        $results = iterator_to_array($this->ask(new ListOrdersWithExpiredBillingRetention('2020-01-01T00:00:00+00:00')), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id()->toString(), $results[0]->id);
    }
}
