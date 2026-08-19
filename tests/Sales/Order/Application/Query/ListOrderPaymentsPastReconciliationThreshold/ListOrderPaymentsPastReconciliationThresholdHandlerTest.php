<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold\ListOrderPaymentsPastReconciliationThreshold;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\MockClock;

final class ListOrderPaymentsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        self::getContainer()->set('clock', new MockClock('2026-02-01T00:00:00+00:00'));
        $stuck = OrderPaymentTestFactory::new()
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->store();
        OrderPaymentTestFactory::new()
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T23:55:00+00:00'))
            ->store();
        OrderPaymentTestFactory::new()
            ->withRequestedAt(new \DateTimeImmutable('2026-01-31T22:00:00+00:00'))
            ->authorized()
            ->store();

        // When
        $results = iterator_to_array($this->ask(new ListOrderPaymentsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
