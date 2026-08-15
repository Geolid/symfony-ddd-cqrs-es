<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ProductNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Product with ID "%s" not found.', $id));
    }
}
