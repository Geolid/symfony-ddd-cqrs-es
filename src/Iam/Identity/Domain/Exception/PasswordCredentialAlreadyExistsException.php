<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class PasswordCredentialAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('PasswordCredential with ID "%s" already exists.', $id));
    }
}
