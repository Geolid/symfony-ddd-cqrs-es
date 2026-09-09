<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class ErasureAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Erasure "%s" already exists.', $id));
    }
}
