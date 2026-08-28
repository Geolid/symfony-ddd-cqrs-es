<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Status;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Status\OrderPaymentStatus;

final class OrderPaymentStatusTest extends TestCase
{
    #[Test]
    public function itIsCaptured(): void
    {
        foreach (OrderPaymentStatus::cases() as $status) {
            self::assertSame(OrderPaymentStatus::CAPTURED === $status, $status->isCaptured(), $status->value);
        }
    }
}
