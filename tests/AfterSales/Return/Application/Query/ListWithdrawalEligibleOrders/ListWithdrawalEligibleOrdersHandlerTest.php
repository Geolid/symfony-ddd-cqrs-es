<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Query\ListWithdrawalEligibleOrders;

use AfterSales\Return\Application\Query\ListWithdrawalEligibleOrders\ListWithdrawalEligibleOrders;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListWithdrawalEligibleOrdersHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itChecksBatch(): void
    {
        // Given
        $eligible = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $expired = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered(Clock::get()->now()->modify('-15 days'))->create();
        $this->store($eligible, $expired);

        // When
        $results = $this->ask(new ListWithdrawalEligibleOrders([$eligible->id->toString(), $expired->id->toString()]));

        // Then
        self::assertTrue($results[$eligible->id->toString()]);
        self::assertFalse($results[$expired->id->toString()]);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = $this->ask(new ListWithdrawalEligibleOrders([]));

        // Then
        self::assertSame([], $results);
    }
}
