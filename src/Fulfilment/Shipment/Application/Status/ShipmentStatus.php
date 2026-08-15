<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Status;

enum ShipmentStatus: string
{
    case REQUESTED = 'requested';
    case PREPARED = 'prepared';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
