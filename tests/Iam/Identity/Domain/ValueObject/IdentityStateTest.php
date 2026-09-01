<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\IdentityState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityStateTest extends TestCase
{
    #[Test]
    public function itIsActive(): void
    {
        foreach (IdentityState::cases() as $state) {
            $isActive = $state->isActive();
            self::assertSame(IdentityState::ACTIVE === $state, $isActive, $state->value);
        }
    }

    #[Test]
    public function itIsSuspended(): void
    {
        foreach (IdentityState::cases() as $state) {
            $isSuspended = $state->isSuspended();
            self::assertSame(IdentityState::SUSPENDED === $state, $isSuspended, $state->value);
        }
    }

    #[Test]
    public function itIsErased(): void
    {
        foreach (IdentityState::cases() as $state) {
            $isErased = $state->isErased();
            self::assertSame(IdentityState::ERASED === $state, $isErased, $state->value);
        }
    }
}
