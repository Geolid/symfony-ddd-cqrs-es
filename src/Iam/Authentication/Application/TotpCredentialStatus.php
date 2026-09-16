<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum TotpCredentialStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REVOKED = 'revoked';

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }
}
