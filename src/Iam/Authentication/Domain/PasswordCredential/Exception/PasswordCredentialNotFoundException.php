<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class PasswordCredentialNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Password credential "%s" not found.', $id));
    }
}
