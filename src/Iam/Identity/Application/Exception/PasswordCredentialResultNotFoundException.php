<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class PasswordCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forLogin(string $login): self
    {
        return new self(\sprintf('PasswordCredential not found for criteria %s.', json_encode(['login' => $login], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
    }

    public static function forIdentityId(string $identityId): self
    {
        return new self(\sprintf('PasswordCredential not found for criteria %s.', json_encode(['identityId' => $identityId], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
    }
}
