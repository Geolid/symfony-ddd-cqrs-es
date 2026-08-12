<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Enum;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
