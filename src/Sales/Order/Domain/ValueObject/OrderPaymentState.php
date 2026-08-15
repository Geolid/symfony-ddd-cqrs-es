<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderPaymentState: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDING = 'refunding';

    public function isRequested(): bool
    {
        return self::REQUESTED === $this;
    }

    public function isAuthorized(): bool
    {
        return self::AUTHORIZED === $this;
    }

    public function isCaptured(): bool
    {
        return self::CAPTURED === $this;
    }

    public function isFailed(): bool
    {
        return self::FAILED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }

    public function isRefunding(): bool
    {
        return self::REFUNDING === $this;
    }
}
