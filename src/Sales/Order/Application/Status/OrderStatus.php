<?php

declare(strict_types=1);

namespace Sales\Order\Application\Status;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case CANCELLED = 'cancelled';
    case CONFIRMED = 'confirmed';
    case DISPATCHED = 'dispatched';
    case COMPLETED = 'completed';
    case RETURN_REQUESTED = 'return_requested';
    case REFUNDING = 'refunding';
    case RETURNED = 'returned';
    case RETURN_REJECTED = 'return_rejected';

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
