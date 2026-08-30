<?php

declare(strict_types=1);

namespace Sales\Order\Application;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case RETURN_REQUESTED = 'return_requested';
    case RETURNED = 'returned';
    case RETURN_REJECTED = 'return_rejected';

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
