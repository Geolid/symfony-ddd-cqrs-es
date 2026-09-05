<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\ApiKeyCredential\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class ApiKeyCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forKeyId(string $keyId): self
    {
        return new self(\sprintf('API key credential of key "%s" not found.', $keyId));
    }
}
