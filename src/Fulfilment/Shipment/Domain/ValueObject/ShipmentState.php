<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

enum ShipmentState: string
{
    case REQUESTED = 'requested';
    case PREPARED = 'prepared';
    case CANCELLED = 'cancelled';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case RETURN_REQUESTED = 'return_requested';
    case RETURN_MANIFESTED = 'return_manifested';
    case RETURN_DISPATCHED = 'return_dispatched';
    case RETURN_RECEIVED = 'return_received';
    case RETURN_APPROVED = 'return_approved';
    case RETURN_REJECTED = 'return_rejected';

    public function isManifested(): bool
    {
        return self::MANIFESTED === $this;
    }

    public function isReturnManifested(): bool
    {
        return self::RETURN_MANIFESTED === $this;
    }
}
