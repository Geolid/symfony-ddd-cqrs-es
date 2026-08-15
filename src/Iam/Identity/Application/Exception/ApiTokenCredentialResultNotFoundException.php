<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ApiTokenCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forIdentifier(string $identifier): self
    {
        return new self(\sprintf('ApiTokenCredential identified by "%s" not found.', $identifier));
    }
}
