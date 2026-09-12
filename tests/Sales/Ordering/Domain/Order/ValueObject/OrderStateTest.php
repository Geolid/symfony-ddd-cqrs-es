<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Domain\Order\ValueObject\OrderState;

final class OrderStateTest extends TestCase
{
    #[Test]
    public function itIsDelivered(): void
    {
        foreach (OrderState::cases() as $state) {
            self::assertSame(OrderState::DELIVERED === $state, $state->isDelivered(), $state->value);
        }
    }

    #[Test]
    public function itIsCancelled(): void
    {
        foreach (OrderState::cases() as $state) {
            self::assertSame(OrderState::CANCELLED === $state, $state->isCancelled(), $state->value);
        }
    }

    #[Test]
    public function itIsFailed(): void
    {
        foreach (OrderState::cases() as $state) {
            self::assertSame(OrderState::FAILED === $state, $state->isFailed(), $state->value);
        }
    }
}
