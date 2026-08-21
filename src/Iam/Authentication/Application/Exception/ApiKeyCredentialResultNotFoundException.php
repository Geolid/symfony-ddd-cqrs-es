<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ApiKeyCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forKeyId(string $keyId): self
    {
        return new self(\sprintf('API key credential with key "%s" not found.', $keyId));
    }
}
