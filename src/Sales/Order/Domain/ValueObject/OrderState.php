<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderState: string
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

    public function isPlaced(): bool
    {
        return self::PLACED === $this;
    }

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }

    public function isDispatched(): bool
    {
        return self::DISPATCHED === $this;
    }

    public function isDelivered(): bool
    {
        return self::DELIVERED === $this;
    }

    public function isCompleted(): bool
    {
        return self::COMPLETED === $this;
    }

    public function isReturnRequested(): bool
    {
        return self::RETURN_REQUESTED === $this;
    }

    public function isReturned(): bool
    {
        return self::RETURNED === $this;
    }

    public function isReturnRejected(): bool
    {
        return self::RETURN_REJECTED === $this;
    }

    public function isCancellable(): bool
    {
        return \in_array($this, self::cancellableStates(), true);
    }

    /**
     * @return list<self>
     */
    private static function cancellableStates(): array
    {
        return [self::PLACED, self::CONFIRMED];
    }
}
