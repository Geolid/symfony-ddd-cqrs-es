<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Domain\ValueObject;

enum ShipmentState: string
{
    case REQUESTED = 'requested';
    case PREPARED = 'prepared';
    case MANIFESTED = 'manifested';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function isRequested(): bool
    {
        return self::REQUESTED === $this;
    }

    public function isPrepared(): bool
    {
        return self::PREPARED === $this;
    }

    public function isManifested(): bool
    {
        return self::MANIFESTED === $this;
    }

    public function isDispatched(): bool
    {
        return self::DISPATCHED === $this;
    }

    public function isDelivered(): bool
    {
        return self::DELIVERED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
