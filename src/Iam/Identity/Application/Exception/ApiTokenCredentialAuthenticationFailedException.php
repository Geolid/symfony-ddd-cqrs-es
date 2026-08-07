<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class ApiTokenCredentialAuthenticationFailedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentifier(string $identifier): self
    {
        return new self(\sprintf('The identifier/secret pair for "%s" was refused.', $identifier));
    }
}
