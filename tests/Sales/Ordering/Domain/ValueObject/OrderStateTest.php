<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Domain\ValueObject\OrderState;

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
}
