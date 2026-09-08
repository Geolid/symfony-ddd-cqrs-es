<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderState: string
{
    case PLACED = 'placed';
    case CONFIRMED = 'confirmed';
    case PREPARED = 'prepared';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function isDelivered(): bool
    {
        return self::DELIVERED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
