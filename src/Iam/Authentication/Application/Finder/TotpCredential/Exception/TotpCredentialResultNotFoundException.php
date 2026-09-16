<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\TotpCredential\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class TotpCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('TOTP credential "%s" not found.', $id));
    }

    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('TOTP credential of identity "%s" not found.', $identityId));
    }
}
