<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Query\ListCheckoutSessionsPastReconciliationThreshold;

use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\Query\ListCheckoutSessionsPastReconciliationThreshold\ListCheckoutSessionsPastReconciliationThreshold;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListCheckoutSessionsPastReconciliationThresholdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $stuck = CheckoutSessionBuilder::new()
            ->withOpenedAt($now->modify('-45 minutes'))
            ->create();
        $fresh = CheckoutSessionBuilder::new()
            ->withOpenedAt($now->modify('-5 minutes'))
            ->create();
        $completed = CheckoutSessionBuilder::new()
            ->withOpenedAt($now->modify('-45 minutes'))
            ->completed()
            ->create();
        $this->store($fresh, $completed, $stuck);

        // When
        $results = iterator_to_array($this->ask(new ListCheckoutSessionsPastReconciliationThreshold()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($stuck->id->toString(), $results[0]->id);
    }
}
