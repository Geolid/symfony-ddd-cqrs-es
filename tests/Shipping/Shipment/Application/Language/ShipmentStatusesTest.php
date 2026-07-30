<?php

declare(strict_types=1);

namespace Shipping\Tests\Shipment\Application\Language;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shipping\Shipment\Application\Language\ShipmentStatuses;
use Shipping\Shipment\Domain\ShipmentStatus;

final class ShipmentStatusesTest extends TestCase
{
    /**
     * Publishing a newly added status is deliberate, so the domain may hold more cases than the
     * catalog. The reverse must never happen: a published value no case backs.
     */
    #[Test]
    public function itPublishesOnlyStatusesTheDomainDefines(): void
    {
        self::assertSame([], array_diff(ShipmentStatuses::ALL, array_column(ShipmentStatus::cases(), 'value')));
    }
}
