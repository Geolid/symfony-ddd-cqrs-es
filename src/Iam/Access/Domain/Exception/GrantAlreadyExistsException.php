<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class GrantAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Grant with ID "%s" already exists.', $id));
    }
}
