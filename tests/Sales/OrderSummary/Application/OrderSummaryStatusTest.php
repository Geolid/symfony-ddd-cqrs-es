<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\OrderSummary\Application\OrderSummaryStatus;

final class OrderSummaryStatusTest extends TestCase
{
    #[Test]
    public function itIsPlaced(): void
    {
        $this->checkOnlyTrueFor(OrderSummaryStatus::PLACED, static fn (OrderSummaryStatus $status): bool => $status->isPlaced());
    }

    #[Test]
    public function itIsCancelled(): void
    {
        $this->checkOnlyTrueFor(OrderSummaryStatus::CANCELLED, static fn (OrderSummaryStatus $status): bool => $status->isCancelled());
    }

    #[Test]
    public function itIsPaymentPending(): void
    {
        $this->checkOnlyTrueFor(OrderSummaryStatus::PAYMENT_PENDING, static fn (OrderSummaryStatus $status): bool => $status->isPaymentPending());
    }

    #[Test]
    public function itIsPreparing(): void
    {
        $this->checkOnlyTrueFor(OrderSummaryStatus::PREPARING, static fn (OrderSummaryStatus $status): bool => $status->isPreparing());
    }

    #[Test]
    public function itIsDispatched(): void
    {
        $this->checkOnlyTrueFor(OrderSummaryStatus::DISPATCHED, static fn (OrderSummaryStatus $status): bool => $status->isDispatched());
    }

    #[Test]
    public function itIsDelivered(): void
    {
        $this->checkOnlyTrueFor(OrderSummaryStatus::DELIVERED, static fn (OrderSummaryStatus $status): bool => $status->isDelivered());
    }

    /**
     * @param callable(OrderSummaryStatus): bool $predicate
     */
    private function checkOnlyTrueFor(OrderSummaryStatus $expected, callable $predicate): void
    {
        foreach (OrderSummaryStatus::cases() as $status) {
            self::assertSame($expected === $status, $predicate($status), $status->value);
        }
    }
}
