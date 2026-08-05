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
}
