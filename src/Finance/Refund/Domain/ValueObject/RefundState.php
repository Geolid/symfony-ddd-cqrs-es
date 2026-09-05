<?php

declare(strict_types=1);

namespace Finance\Refund\Domain\ValueObject;

enum RefundState: string
{
    case INITIATED = 'initiated';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';

    public function isInitiated(): bool
    {
        return self::INITIATED === $this;
    }
}
