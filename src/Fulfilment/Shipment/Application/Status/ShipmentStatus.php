<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Status;

enum ShipmentStatus: string
{
    case PENDING = 'pending';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
