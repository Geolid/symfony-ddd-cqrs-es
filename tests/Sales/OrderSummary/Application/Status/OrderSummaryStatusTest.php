<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Status;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\OrderSummary\Application\Status\OrderSummaryStatus;

final class OrderSummaryStatusTest extends TestCase
{
    #[Test]
    public function itIsPlacedOnlyWhenPlaced(): void
    {
        $this->assertOnlyTrueFor(OrderSummaryStatus::PLACED, static fn (OrderSummaryStatus $status): bool => $status->isPlaced());
    }

    #[Test]
    public function itIsCancelledOnlyWhenCancelled(): void
    {
        $this->assertOnlyTrueFor(OrderSummaryStatus::CANCELLED, static fn (OrderSummaryStatus $status): bool => $status->isCancelled());
    }

    #[Test]
    public function itIsPaymentPendingOnlyWhenPaymentPending(): void
    {
        $this->assertOnlyTrueFor(OrderSummaryStatus::PAYMENT_PENDING, static fn (OrderSummaryStatus $status): bool => $status->isPaymentPending());
    }

    #[Test]
    public function itIsPreparingOnlyWhenPreparing(): void
    {
        $this->assertOnlyTrueFor(OrderSummaryStatus::PREPARING, static fn (OrderSummaryStatus $status): bool => $status->isPreparing());
    }

    #[Test]
    public function itIsDispatchedOnlyWhenDispatched(): void
    {
        $this->assertOnlyTrueFor(OrderSummaryStatus::DISPATCHED, static fn (OrderSummaryStatus $status): bool => $status->isDispatched());
    }

    #[Test]
    public function itIsDeliveredOnlyWhenDelivered(): void
    {
        $this->assertOnlyTrueFor(OrderSummaryStatus::DELIVERED, static fn (OrderSummaryStatus $status): bool => $status->isDelivered());
    }

    /**
     * @param callable(OrderSummaryStatus): bool $predicate
     */
    private function assertOnlyTrueFor(OrderSummaryStatus $expected, callable $predicate): void
    {
        foreach (OrderSummaryStatus::cases() as $status) {
            self::assertSame($expected === $status, $predicate($status), $status->value);
        }
    }
}
