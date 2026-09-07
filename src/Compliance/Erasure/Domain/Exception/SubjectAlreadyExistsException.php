<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class SubjectAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Subject "%s" already exists.', $id));
    }
}
