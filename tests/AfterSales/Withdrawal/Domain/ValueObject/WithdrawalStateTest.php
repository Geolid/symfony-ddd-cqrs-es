<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Domain\ValueObject;

use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WithdrawalStateTest extends TestCase
{
    #[Test]
    public function itIsReceived(): void
    {
        foreach (WithdrawalState::cases() as $state) {
            self::assertSame(WithdrawalState::RECEIVED === $state, $state->isReceived(), $state->value);
        }
    }

    #[Test]
    public function itIsApproved(): void
    {
        foreach (WithdrawalState::cases() as $state) {
            self::assertSame(WithdrawalState::APPROVED === $state, $state->isApproved(), $state->value);
        }
    }

    #[Test]
    public function itIsRejected(): void
    {
        foreach (WithdrawalState::cases() as $state) {
            self::assertSame(WithdrawalState::REJECTED === $state, $state->isRejected(), $state->value);
        }
    }
}
