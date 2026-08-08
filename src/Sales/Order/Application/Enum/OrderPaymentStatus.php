<?php

declare(strict_types=1);

namespace Sales\Order\Application\Enum;

enum OrderPaymentStatus: string
{
    case REQUESTED = 'requested';
    case CAPTURED = 'captured';

    public function isCaptured(): bool
    {
        return self::CAPTURED === $this;
    }
}
