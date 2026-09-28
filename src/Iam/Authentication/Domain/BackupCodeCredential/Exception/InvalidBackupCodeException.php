<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Exception;

use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;

final class InvalidBackupCodeException extends \DomainException
{
    public static function forId(BackupCodeCredentialId $id): self
    {
        return new self(\sprintf('The backup code provided for "%s" is invalid.', $id->toString()));
    }
}
