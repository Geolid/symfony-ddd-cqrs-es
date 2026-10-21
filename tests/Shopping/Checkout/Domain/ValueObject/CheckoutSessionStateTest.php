<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionState;

final class CheckoutSessionStateTest extends TestCase
{
    #[Test]
    public function itIsOpen(): void
    {
        foreach (CheckoutSessionState::cases() as $state) {
            self::assertSame(CheckoutSessionState::OPEN === $state, $state->isOpen(), $state->value);
        }
    }

    #[Test]
    public function itIsExpired(): void
    {
        foreach (CheckoutSessionState::cases() as $state) {
            self::assertSame(CheckoutSessionState::EXPIRED === $state, $state->isExpired(), $state->value);
        }
    }

    #[Test]
    public function itIsStale(): void
    {
        foreach (CheckoutSessionState::cases() as $state) {
            self::assertSame(CheckoutSessionState::STALE === $state, $state->isStale(), $state->value);
        }
    }

    #[Test]
    public function itIsCompleted(): void
    {
        foreach (CheckoutSessionState::cases() as $state) {
            self::assertSame(CheckoutSessionState::COMPLETED === $state, $state->isCompleted(), $state->value);
        }
    }
}
