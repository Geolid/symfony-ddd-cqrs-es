<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Exception;

use Shared\Application\Exception\ResultNotFoundException;

final class ProductResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Product with ID "%s" not found.', $id));
    }
}
