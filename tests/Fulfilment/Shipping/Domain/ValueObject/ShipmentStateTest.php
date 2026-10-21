<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Domain\ValueObject;

use Fulfilment\Shipping\Domain\ValueObject\ShipmentState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShipmentStateTest extends TestCase
{
    #[Test]
    public function itIsRequested(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::REQUESTED === $state, $state->isRequested(), $state->value);
        }
    }

    #[Test]
    public function itIsPrepared(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::PREPARED === $state, $state->isPrepared(), $state->value);
        }
    }

    #[Test]
    public function itIsManifested(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::MANIFESTED === $state, $state->isManifested(), $state->value);
        }
    }

    #[Test]
    public function itIsDispatched(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::DISPATCHED === $state, $state->isDispatched(), $state->value);
        }
    }

    #[Test]
    public function itIsDelivered(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::DELIVERED === $state, $state->isDelivered(), $state->value);
        }
    }

    #[Test]
    public function itIsCancelled(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::CANCELLED === $state, $state->isCancelled(), $state->value);
        }
    }
}
