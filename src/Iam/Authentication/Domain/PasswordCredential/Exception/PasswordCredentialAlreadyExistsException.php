<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class PasswordCredentialAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Password credential "%s" already exists.', $id));
    }
}
