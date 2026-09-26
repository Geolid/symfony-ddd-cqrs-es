<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\BackupCodeRegeneration\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BackupCodeCredentialNotGeneratedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Identity "%s" has no backup code credential generated.', $identityId));
    }
}
