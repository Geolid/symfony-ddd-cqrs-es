<?php

declare(strict_types=1);

namespace Finance\Payment\Application;

enum PaymentStatus: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUND_INITIATED = 'refund_initiated';
    case REFUNDED = 'refunded';

    public function isCaptured(): bool
    {
        return self::CAPTURED === $this;
    }
}
