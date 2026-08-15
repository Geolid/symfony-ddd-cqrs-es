<?php

declare(strict_types=1);

namespace Sales\Order\Application\Enum;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case CANCELLED = 'cancelled';
    case CONFIRMED = 'confirmed';
    case DISPATCHED = 'dispatched';
    case COMPLETED = 'completed';

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
