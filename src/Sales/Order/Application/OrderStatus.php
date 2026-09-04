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

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
