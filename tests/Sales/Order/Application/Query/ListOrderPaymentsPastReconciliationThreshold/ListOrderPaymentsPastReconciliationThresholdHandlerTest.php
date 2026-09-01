<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold\ListOrderPaymentsPastReconciliationThreshold;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListOrderPaymentsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $stuck = OrderPaymentBuilder::new()
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create();
        $fresh = OrderPaymentBuilder::new()
            ->withRequestedAt($now->modify('-5 minutes'))
            ->create();
        $authorized = OrderPaymentBuilder::new()
            ->withRequestedAt($now->modify('-90 minutes'))
            ->authorized()
            ->create();
        $this->store($fresh, $authorized, $stuck);

        // When
        $results = iterator_to_array($this->ask(new ListOrderPaymentsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
