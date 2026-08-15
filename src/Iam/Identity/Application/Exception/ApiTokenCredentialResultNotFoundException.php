<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ApiTokenCredentialResultNotFoundException extends ResultNotFoundException
{
    public static function forIdentifier(string $identifier): self
    {
        return new self(\sprintf('ApiTokenCredential not found for criteria %s.', json_encode(['identifier' => $identifier], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)));
    }
}
