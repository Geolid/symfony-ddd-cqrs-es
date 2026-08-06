<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\OrderSummary\Application\Enum\AppOrderSummaryStatus;

final class AppOrderSummaryStatusTest extends TestCase
{
    #[Test]
    public function itIsPlacedOnlyWhenPlaced(): void
    {
        self::assertOnlyTrueFor(AppOrderSummaryStatus::PLACED, static fn (AppOrderSummaryStatus $status): bool => $status->isPlaced());
    }

    #[Test]
    public function itIsCancelledOnlyWhenCancelled(): void
    {
        self::assertOnlyTrueFor(AppOrderSummaryStatus::CANCELLED, static fn (AppOrderSummaryStatus $status): bool => $status->isCancelled());
    }

    #[Test]
    public function itIsPaymentPendingOnlyWhenPaymentPending(): void
    {
        self::assertOnlyTrueFor(AppOrderSummaryStatus::PAYMENT_PENDING, static fn (AppOrderSummaryStatus $status): bool => $status->isPaymentPending());
    }

    #[Test]
    public function itIsPreparingOnlyWhenPreparing(): void
    {
        self::assertOnlyTrueFor(AppOrderSummaryStatus::PREPARING, static fn (AppOrderSummaryStatus $status): bool => $status->isPreparing());
    }

    #[Test]
    public function itIsDispatchedOnlyWhenDispatched(): void
    {
        self::assertOnlyTrueFor(AppOrderSummaryStatus::DISPATCHED, static fn (AppOrderSummaryStatus $status): bool => $status->isDispatched());
    }

    #[Test]
    public function itIsDeliveredOnlyWhenDelivered(): void
    {
        self::assertOnlyTrueFor(AppOrderSummaryStatus::DELIVERED, static fn (AppOrderSummaryStatus $status): bool => $status->isDelivered());
    }

    #[Test]
    public function itHasNoProgressionStepOnceCancelled(): void
    {
        self::assertNull(AppOrderSummaryStatus::CANCELLED->progressionStep());
    }

    #[Test]
    public function itOrdersTheProgressionSteps(): void
    {
        self::assertSame(0, AppOrderSummaryStatus::PLACED->progressionStep());
        self::assertSame(1, AppOrderSummaryStatus::PAYMENT_PENDING->progressionStep());
        self::assertSame(2, AppOrderSummaryStatus::PREPARING->progressionStep());
        self::assertSame(3, AppOrderSummaryStatus::DISPATCHED->progressionStep());
        self::assertSame(4, AppOrderSummaryStatus::DELIVERED->progressionStep());
    }

    /**
     * @param callable(AppOrderSummaryStatus): bool $predicate
     */
    private static function assertOnlyTrueFor(AppOrderSummaryStatus $expected, callable $predicate): void
    {
        foreach (AppOrderSummaryStatus::cases() as $status) {
            self::assertSame($expected === $status, $predicate($status), $status->value);
        }
    }
}
