<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ApiTokenCredentialResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentifier(string $identifier): self
    {
        return new self(\sprintf('No API token credential carries the identifier "%s".', $identifier));
    }
}
