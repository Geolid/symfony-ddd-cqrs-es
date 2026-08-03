<?php

declare(strict_types=1);

namespace Sales\Order\Domain\ValueObject;

enum OrderPaymentStatus: string
{
    case REQUESTED = 'requested';
    case CAPTURED = 'captured';

    public function isRequested(): bool
    {
        return self::REQUESTED === $this;
    }

    public function isCaptured(): bool
    {
        return self::CAPTURED === $this;
    }
}
