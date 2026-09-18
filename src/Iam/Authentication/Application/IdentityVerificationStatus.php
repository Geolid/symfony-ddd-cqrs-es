<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum IdentityVerificationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }
}
