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

    public function isAbandoned(): bool
    {
        return self::ABANDONED === $this;
    }

    public function isVoided(): bool
    {
        return self::VOIDED === $this;
    }
}
