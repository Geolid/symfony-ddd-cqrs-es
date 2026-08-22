<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class PasswordCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forLogin(string $login): self
    {
        return new self(\sprintf('Password credential of login "%s" not found.', $login));
    }

    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Password credential of identity "%s" not found.', $identityId));
    }
}
