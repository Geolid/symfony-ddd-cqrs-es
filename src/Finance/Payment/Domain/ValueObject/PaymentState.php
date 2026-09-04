<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\ValueObject;

enum PaymentState: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case FAILED = 'failed';
    case CAPTURED = 'captured';
    case CANCELLED = 'cancelled';
    case REFUND_INITIATED = 'refund_initiated';
    case REFUNDED = 'refunded';

    public function isRequested(): bool
    {
        return self::REQUESTED === $this;
    }

    public function isAuthorized(): bool
    {
        return self::AUTHORIZED === $this;
    }

    public function isFailed(): bool
    {
        return self::FAILED === $this;
    }

    public function isCaptured(): bool
    {
        return self::CAPTURED === $this;
    }

    public function isCancelled(): bool
    {
        return self::CANCELLED === $this;
    }

    public function isRefundInitiated(): bool
    {
        return self::REFUND_INITIATED === $this;
    }

    public function isRefunded(): bool
    {
        return self::REFUNDED === $this;
    }
}
