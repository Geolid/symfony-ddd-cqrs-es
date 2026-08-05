<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Enum;

enum AppOrderSummaryStatus: string
{
    case PLACED = 'placed';
    case PAYMENT_PENDING = 'payment_pending';
    case PREPARING = 'preparing';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function isPlaced(): bool
    {
        return self::PLACED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
