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
        // Then
        self::assertTrue(AppOrderSummaryStatus::PLACED->isPlaced());
        self::assertFalse(AppOrderSummaryStatus::CANCELLED->isPlaced());
    }

    #[Test]
    public function itIsCancelledOnlyWhenCancelled(): void
    {
        // Then
        self::assertTrue(AppOrderSummaryStatus::CANCELLED->isCancelled());
        self::assertFalse(AppOrderSummaryStatus::PLACED->isCancelled());
    }

    #[Test]
    public function itIsPaymentPendingOnlyWhenPaymentPending(): void
    {
        // Then
        self::assertTrue(AppOrderSummaryStatus::PAYMENT_PENDING->isPaymentPending());
        self::assertFalse(AppOrderSummaryStatus::PLACED->isPaymentPending());
    }

    #[Test]
    public function itIsPreparingOnlyWhenPreparing(): void
    {
        // Then
        self::assertTrue(AppOrderSummaryStatus::PREPARING->isPreparing());
        self::assertFalse(AppOrderSummaryStatus::PLACED->isPreparing());
    }

    #[Test]
    public function itIsDispatchedOnlyWhenDispatched(): void
    {
        // Then
        self::assertTrue(AppOrderSummaryStatus::DISPATCHED->isDispatched());
        self::assertFalse(AppOrderSummaryStatus::PLACED->isDispatched());
    }

    #[Test]
    public function itIsDeliveredOnlyWhenDelivered(): void
    {
        // Then
        self::assertTrue(AppOrderSummaryStatus::DELIVERED->isDelivered());
        self::assertFalse(AppOrderSummaryStatus::PLACED->isDelivered());
    }
}
