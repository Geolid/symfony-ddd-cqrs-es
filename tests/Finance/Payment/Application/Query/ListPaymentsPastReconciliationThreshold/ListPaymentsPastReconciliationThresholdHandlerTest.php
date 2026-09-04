<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Query\ListPaymentsPastReconciliationThreshold;

use Finance\Payment\Application\Query\ListPaymentsPastReconciliationThreshold\ListPaymentsPastReconciliationThreshold;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListPaymentsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $stuck = PaymentBuilder::new()
            ->withRequestedAt($now->modify('-90 minutes'))
            ->create();
        $fresh = PaymentBuilder::new()
            ->withRequestedAt($now->modify('-5 minutes'))
            ->create();
        $authorized = PaymentBuilder::new()
            ->withRequestedAt($now->modify('-90 minutes'))
            ->authorized()
            ->create();
        $this->store($fresh, $authorized, $stuck);

        // When
        $results = iterator_to_array($this->ask(new ListPaymentsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
