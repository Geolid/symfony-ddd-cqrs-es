<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Domain\ValueObject;

use Finance\Payment\Domain\ValueObject\PaymentState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaymentStateTest extends TestCase
{
    #[Test]
    public function itIsRequested(): void
    {
        foreach (PaymentState::cases() as $state) {
            self::assertSame(PaymentState::REQUESTED === $state, $state->isRequested(), $state->value);
        }
    }

    #[Test]
    public function itIsAuthorized(): void
    {
        foreach (PaymentState::cases() as $state) {
            self::assertSame(PaymentState::AUTHORIZED === $state, $state->isAuthorized(), $state->value);
        }
    }

    #[Test]
    public function itIsCaptured(): void
    {
        foreach (PaymentState::cases() as $state) {
            self::assertSame(PaymentState::CAPTURED === $state, $state->isCaptured(), $state->value);
        }
    }

    #[Test]
    public function itIsFailed(): void
    {
        foreach (PaymentState::cases() as $state) {
            self::assertSame(PaymentState::FAILED === $state, $state->isFailed(), $state->value);
        }
    }

    #[Test]
    public function itIsAbandoned(): void
    {
        foreach (PaymentState::cases() as $state) {
            self::assertSame(PaymentState::ABANDONED === $state, $state->isAbandoned(), $state->value);
        }
    }

    #[Test]
    public function itIsVoided(): void
    {
        foreach (PaymentState::cases() as $state) {
            self::assertSame(PaymentState::VOIDED === $state, $state->isVoided(), $state->value);
        }
    }
}
