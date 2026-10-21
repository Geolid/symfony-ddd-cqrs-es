<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Domain\ValueObject;

use Compliance\Erasing\Domain\ValueObject\ErasureRequestState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ErasureRequestStateTest extends TestCase
{
    #[Test]
    public function itIsRequested(): void
    {
        foreach (ErasureRequestState::cases() as $state) {
            self::assertSame(ErasureRequestState::REQUESTED === $state, $state->isRequested(), $state->value);
        }
    }

    #[Test]
    public function itIsCancelled(): void
    {
        foreach (ErasureRequestState::cases() as $state) {
            self::assertSame(ErasureRequestState::CANCELLED === $state, $state->isCancelled(), $state->value);
        }
    }

    #[Test]
    public function itIsApproved(): void
    {
        foreach (ErasureRequestState::cases() as $state) {
            self::assertSame(ErasureRequestState::APPROVED === $state, $state->isApproved(), $state->value);
        }
    }
}
