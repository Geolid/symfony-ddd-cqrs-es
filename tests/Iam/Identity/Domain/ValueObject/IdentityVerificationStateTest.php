<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\IdentityVerificationState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityVerificationStateTest extends TestCase
{
    #[Test]
    public function itIsPending(): void
    {
        foreach (IdentityVerificationState::cases() as $state) {
            self::assertSame(IdentityVerificationState::PENDING === $state, $state->isPending(), $state->value);
        }
    }

    #[Test]
    public function itIsConfirmed(): void
    {
        foreach (IdentityVerificationState::cases() as $state) {
            self::assertSame(IdentityVerificationState::CONFIRMED === $state, $state->isConfirmed(), $state->value);
        }
    }
}
