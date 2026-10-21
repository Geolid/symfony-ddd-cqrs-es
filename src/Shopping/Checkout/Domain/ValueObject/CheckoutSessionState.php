<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\ValueObject;

enum CheckoutSessionState: string
{
    case OPEN = 'open';
    case EXPIRED = 'expired';
    case STALE = 'stale';
    case COMPLETED = 'completed';

    public function isOpen(): bool
    {
        return self::OPEN === $this;
    }

    public function isExpired(): bool
    {
        return self::EXPIRED === $this;
    }

    public function isStale(): bool
    {
        return self::STALE === $this;
    }

    public function isCompleted(): bool
    {
        return self::COMPLETED === $this;
    }
}
