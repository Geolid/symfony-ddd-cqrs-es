<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\BackupCodeIssuance\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

final class BackupCodeCredentialNotIssuedException extends \RuntimeException implements ApplicationExceptionInterface
{
    public static function forIdentity(string $identityId): self
    {
        return new self(\sprintf('Identity "%s" has no backup code credential issued.', $identityId));
    }
}
