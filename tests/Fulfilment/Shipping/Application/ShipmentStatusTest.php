<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application;

use Fulfilment\Shipping\Application\ShipmentStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShipmentStatusTest extends TestCase
{
    #[Test]
    public function itIsCancelled(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            self::assertSame(ShipmentStatus::CANCELLED === $status, $status->isCancelled(), $status->value);
        }
    }
}
