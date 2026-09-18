<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\ValueObject;

use Iam\Identity\Domain\ValueObject\IdentityModerationState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityModerationStateTest extends TestCase
{
    #[Test]
    public function itIsActive(): void
    {
        foreach (IdentityModerationState::cases() as $state) {
            self::assertSame(IdentityModerationState::ACTIVE === $state, $state->isActive(), $state->value);
        }
    }

    #[Test]
    public function itIsSuspended(): void
    {
        foreach (IdentityModerationState::cases() as $state) {
            self::assertSame(IdentityModerationState::SUSPENDED === $state, $state->isSuspended(), $state->value);
        }
    }
}
