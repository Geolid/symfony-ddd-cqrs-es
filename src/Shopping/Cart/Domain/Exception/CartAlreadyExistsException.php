<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class CartAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Cart "%s" already exists.', $id));
    }
}
