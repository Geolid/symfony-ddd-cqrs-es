<?php

declare(strict_types=1);

namespace Sales\Order\Application;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case CONFIRMED = 'confirmed';
    case PREPARED = 'prepared';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case RETURN_REQUESTED = 'return_requested';
    case RETURNED = 'returned';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
