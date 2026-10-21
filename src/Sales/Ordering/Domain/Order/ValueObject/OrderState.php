<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\ValueObject;

enum OrderState: string
{
    case CONFIRMED = 'confirmed';
    case PREPARED = 'prepared';
    case PAID = 'paid';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }

    public function isPrepared(): bool
    {
        return self::PREPARED === $this;
    }

    public function isPaid(): bool
    {
        return self::PAID === $this;
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

    public function isFailed(): bool
    {
        return self::FAILED === $this;
    }
}
