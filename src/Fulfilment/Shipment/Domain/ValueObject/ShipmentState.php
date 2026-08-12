<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

enum ShipmentState: string
{
    case PENDING = 'pending';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function isPending(): bool
    {
        return self::PENDING === $this;
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
