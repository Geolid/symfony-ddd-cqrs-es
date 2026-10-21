<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Cart\Domain\ValueObject\CartState;

final class CartStateTest extends TestCase
{
    #[Test]
    public function itIsActive(): void
    {
        foreach (CartState::cases() as $state) {
            self::assertSame(CartState::ACTIVE === $state, $state->isActive(), $state->value);
        }
    }

    #[Test]
    public function itIsPurchased(): void
    {
        foreach (CartState::cases() as $state) {
            self::assertSame(CartState::PURCHASED === $state, $state->isPurchased(), $state->value);
        }
    }
}
