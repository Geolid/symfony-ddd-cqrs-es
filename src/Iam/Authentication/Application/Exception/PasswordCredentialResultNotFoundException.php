<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class PasswordCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forLogin(string $login): self
    {
        return new self(\sprintf('Password credential with login "%s" not found.', $login));
    }

    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Password credential for identity "%s" not found.', $identityId));
    }
}
