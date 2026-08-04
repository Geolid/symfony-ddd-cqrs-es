<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Language;

use Fulfilment\Shipment\Domain\ValueObject\ShipmentStatus;
use Shared\Application\Language\PublishedLanguageInterface;

enum PublishedShipmentStatus: string implements PublishedLanguageInterface
{
    case PENDING = ShipmentStatus::PENDING->value;
    case DISPATCHED = ShipmentStatus::DISPATCHED->value;
    case DELIVERED = ShipmentStatus::DELIVERED->value;
}
