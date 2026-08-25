<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class IdentityAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Identity "%s" already exists.', $id));
    }
}
