<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Enum;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Application\Enum\AppOrderPaymentStatus;

final class AppOrderPaymentStatusTest extends TestCase
{
    #[Test]
    public function itIsCapturedOnlyWhenCaptured(): void
    {
        foreach (AppOrderPaymentStatus::cases() as $status) {
            self::assertSame(AppOrderPaymentStatus::CAPTURED === $status, $status->isCaptured(), $status->value);
        }
    }
}
