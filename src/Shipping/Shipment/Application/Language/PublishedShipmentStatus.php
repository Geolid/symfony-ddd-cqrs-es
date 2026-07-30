<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Language;

use Shared\Application\Language\PublishedLanguageInterface;
use Shipping\Shipment\Domain\ShipmentStatus;

enum PublishedShipmentStatus: string implements PublishedLanguageInterface
{
    case PENDING = ShipmentStatus::PENDING->value;
    case DISPATCHED = ShipmentStatus::DISPATCHED->value;
    case DELIVERED = ShipmentStatus::DELIVERED->value;
}
