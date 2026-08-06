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

    public function isPaymentPending(): bool
    {
        return self::PAYMENT_PENDING === $this;
    }

    public function isPreparing(): bool
    {
        return self::PREPARING === $this;
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

    /**
     * Position among the non-cancelled progression, for a UI progress indicator. Null once cancelled — the
     * progression no longer applies.
     */
    public function progressionStep(): ?int
    {
        return match ($this) {
            self::PLACED => 0,
            self::PAYMENT_PENDING => 1,
            self::PREPARING => 2,
            self::DISPATCHED => 3,
            self::DELIVERED => 4,
            self::CANCELLED => null,
        };
    }
}
