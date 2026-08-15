<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Domain\ValueObject;

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

    public function isCancellable(): bool
    {
        return \in_array($this, self::cancellableStates(), true);
    }

    /**
     * @return list<self>
     */
    private static function cancellableStates(): array
    {
        return [self::REQUESTED, self::PREPARED];
    }
}
