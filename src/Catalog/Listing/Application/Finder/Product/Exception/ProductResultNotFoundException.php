<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Finder\Product\Exception;

use Shared\Application\Finder\Exception\ResultNotFoundException;

final class ProductResultNotFoundException extends ResultNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Product "%s" not found.', $id));
    }
}
