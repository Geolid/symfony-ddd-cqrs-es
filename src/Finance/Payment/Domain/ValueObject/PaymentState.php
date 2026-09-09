<?php

declare(strict_types=1);

namespace Finance\Payment\Domain\ValueObject;

enum PaymentState: string
{
    case REQUESTED = 'requested';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case ABANDONED = 'abandoned';
    case VOIDED = 'voided';

    public function isAbandoned(): bool
    {
        return self::ABANDONED === $this;
    }
}
