<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\ErasureState;

final class ErasureStateTest extends TestCase
{
    #[Test]
    public function itIsRetained(): void
    {
        foreach (ErasureState::cases() as $state) {
            self::assertSame(ErasureState::RETAINED === $state, $state->isRetained(), $state->value);
        }
    }

    #[Test]
    public function itIsApproved(): void
    {
        foreach (ErasureState::cases() as $state) {
            self::assertSame(ErasureState::APPROVED === $state, $state->isApproved(), $state->value);
        }
    }

    #[Test]
    public function itIsErased(): void
    {
        foreach (ErasureState::cases() as $state) {
            self::assertSame(ErasureState::ERASED === $state, $state->isErased(), $state->value);
        }
    }
}
