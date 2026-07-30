<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Language;

use Shared\Application\Language\PublishedLanguageInterface;
use Shipping\Shipment\Domain\ShipmentStatus;

final class ShipmentStatuses implements PublishedLanguageInterface
{
    public const string PENDING = ShipmentStatus::PENDING->value;
    public const string DISPATCHED = ShipmentStatus::DISPATCHED->value;
    public const string DELIVERED = ShipmentStatus::DELIVERED->value;

    /**
     * Enumerated rather than derived from ShipmentStatus::cases() so that publishing a newly
     * added status to an external contract stays a deliberate act, and so that the list can be
     * read from inside an attribute argument, where a method call is not a constant expression.
     */
    public const array ALL = [self::PENDING, self::DISPATCHED, self::DELIVERED];
}
