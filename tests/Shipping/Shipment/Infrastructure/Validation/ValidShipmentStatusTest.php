<?php

declare(strict_types=1);

namespace Shipping\Tests\Shipment\Infrastructure\Validation;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shipping\Shipment\Domain\ShipmentStatus;
use Shipping\Shipment\Infrastructure\Validation\ValidShipmentStatus;

final class ValidShipmentStatusTest extends TestCase
{
    #[Test]
    public function itExposesEveryDomainStatus(): void
    {
        self::assertSame(array_column(ShipmentStatus::cases(), 'value'), ValidShipmentStatus::VALUES);
    }
}
