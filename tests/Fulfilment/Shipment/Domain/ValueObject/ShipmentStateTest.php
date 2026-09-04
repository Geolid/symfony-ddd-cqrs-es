<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Domain\ValueObject;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShipmentStateTest extends TestCase
{
    #[Test]
    public function itIsManifested(): void
    {
        foreach (ShipmentState::cases() as $state) {
            self::assertSame(ShipmentState::MANIFESTED === $state, $state->isManifested(), $state->value);
        }
    }
}
