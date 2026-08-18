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
    case RETURN_REQUESTED = 'return_requested';
    case RETURN_MANIFESTED = 'return_manifested';
    case RETURN_DISPATCHED = 'return_dispatched';
    case RETURN_RECEIVED = 'return_received';
    case RETURN_APPROVED = 'return_approved';
    case RETURN_REJECTED = 'return_rejected';
}
