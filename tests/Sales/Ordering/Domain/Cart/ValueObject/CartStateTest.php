<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Cart\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Domain\Cart\ValueObject\CartState;

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
    public function itIsCheckout(): void
    {
        foreach (CartState::cases() as $state) {
            self::assertSame(CartState::CHECKOUT === $state, $state->isCheckout(), $state->value);
        }
    }

    #[Test]
    public function itIsConverted(): void
    {
        foreach (CartState::cases() as $state) {
            self::assertSame(CartState::CONVERTED === $state, $state->isConverted(), $state->value);
        }
    }
}
