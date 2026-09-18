<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum IdentityVerificationState: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';

    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }
}
