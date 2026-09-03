<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\OrderPaymentStatus;

final class OrderPaymentStatusTest extends TestCase
{
    #[Test]
    public function itIsCaptured(): void
    {
        foreach (OrderPaymentStatus::cases() as $status) {
            $isCaptured = $status->isCaptured();
            self::assertSame(OrderPaymentStatus::CAPTURED === $status, $isCaptured, $status->value);
        }
    }
}
