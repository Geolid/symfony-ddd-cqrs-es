<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\ValueObject;

enum PaymentState: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case FAILED = 'failed';
    case CAPTURED = 'captured';
    case REFUNDING = 'refunding';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

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

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }
}
