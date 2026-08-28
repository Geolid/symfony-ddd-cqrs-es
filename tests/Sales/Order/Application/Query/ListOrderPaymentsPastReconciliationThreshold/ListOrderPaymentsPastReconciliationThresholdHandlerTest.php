<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold\ListOrderPaymentsPastReconciliationThreshold;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListOrderPaymentsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $stuck = OrderPaymentTestFactory::new()
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create();
        $this->store(
            OrderPaymentTestFactory::new()
                ->withRequestedAt($now->modify('-5 minutes'))
                ->create(),
            OrderPaymentTestFactory::new()
                ->withRequestedAt($now->modify('-90 minutes'))
                ->authorized()
                ->create(),
            $stuck,
        );

        // When
        $results = iterator_to_array($this->ask(new ListOrderPaymentsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
