<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class BackupCodeCredentialAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Backup code credential "%s" already exists.', $id));
    }
}
