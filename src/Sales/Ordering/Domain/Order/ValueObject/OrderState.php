<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\ValueObject;

enum OrderState: string
{
    case CONFIRMED = 'confirmed';
    case PREPARED = 'prepared';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function isDelivered(): bool
    {
        return self::DELIVERED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }

    public function isFailed(): bool
    {
        return self::FAILED === $this;
    }
}
