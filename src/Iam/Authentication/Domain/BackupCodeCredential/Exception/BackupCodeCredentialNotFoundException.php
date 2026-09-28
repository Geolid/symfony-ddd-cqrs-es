<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class BackupCodeCredentialNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Backup code credential "%s" not found.', $id));
    }
}
