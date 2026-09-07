<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Domain\ValueObject;

use Compliance\Erasure\Domain\ValueObject\SubjectState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SubjectStateTest extends TestCase
{
    #[Test]
    public function itIsErasing(): void
    {
        foreach (SubjectState::cases() as $state) {
            self::assertSame(SubjectState::ERASING === $state, $state->isErasing(), $state->value);
        }
    }

    #[Test]
    public function itIsErased(): void
    {
        foreach (SubjectState::cases() as $state) {
            self::assertSame(SubjectState::ERASED === $state, $state->isErased(), $state->value);
        }
    }
}
