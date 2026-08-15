<?php

declare(strict_types=1);

namespace Sales\Order\Application\Status;

enum OrderPaymentStatus: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDING = 'refunding';

    public function isCaptured(): bool
    {
        return self::CAPTURED === $this;
    }
}
