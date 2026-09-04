<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Exception;

use Shared\Domain\Exception\AggregateAlreadyExistsException;

final class ProductAlreadyExistsException extends AggregateAlreadyExistsException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Product "%s" already exists.', $id));
    }
}
