<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application;

enum ShipmentStatus: string
{
    case REQUESTED = 'requested';
    case PREPARED = 'prepared';
    case CANCELLED = 'cancelled';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
}
