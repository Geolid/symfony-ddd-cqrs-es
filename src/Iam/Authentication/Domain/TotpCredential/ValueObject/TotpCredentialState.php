<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\ValueObject;

enum TotpCredentialState: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REVOKED = 'revoked';

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }

    public function isRevoked(): bool
    {
        return self::REVOKED === $this;
    }
}
