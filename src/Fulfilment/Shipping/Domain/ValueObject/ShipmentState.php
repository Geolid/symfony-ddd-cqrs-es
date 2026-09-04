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

    /**
     * @phpstan-pure
     */
    public function isManifested(): bool
    {
        return self::MANIFESTED === $this;
    }
}
