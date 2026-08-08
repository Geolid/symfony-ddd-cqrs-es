<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Enum\OrderPaymentStatus;

final class OrderPaymentStatusTest extends TestCase
{
    #[Test]
    public function itIsCapturedOnlyWhenCaptured(): void
    {
        foreach (OrderPaymentStatus::cases() as $status) {
            self::assertSame(OrderPaymentStatus::CAPTURED === $status, $status->isCaptured(), $status->value);
        }
    }
}
