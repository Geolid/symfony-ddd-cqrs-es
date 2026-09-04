<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application;

enum ShipmentStatus: string
{
    case REQUESTED = 'requested';
    case PREPARED = 'prepared';
    case CANCELLED = 'cancelled';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
}
