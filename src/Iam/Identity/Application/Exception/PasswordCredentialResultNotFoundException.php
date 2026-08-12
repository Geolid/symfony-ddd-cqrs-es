<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class PasswordCredentialResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forLogin(string $login): self
    {
        return new self(\sprintf('Password credential with login "%s" not found.', $login));
    }

    public static function forIdentityId(string $identityId): self
    {
        return new self(\sprintf('Password credential for identity ID "%s" not found.', $identityId));
    }
}
