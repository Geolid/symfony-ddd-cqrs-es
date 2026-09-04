<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application;

use Finance\Payment\Application\PaymentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaymentStatusTest extends TestCase
{
    #[Test]
    public function itIsCaptured(): void
    {
        foreach (PaymentStatus::cases() as $status) {
            $isCaptured = $status->isCaptured();
            self::assertSame(PaymentStatus::CAPTURED === $status, $isCaptured, $status->value);
        }
    }
}
