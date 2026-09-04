<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Exception;

use Shared\Domain\Exception\AggregateNotFoundException;

final class ProductNotFoundException extends AggregateNotFoundException
{
    public static function forId(string $id): self
    {
        return new self(\sprintf('Product "%s" not found.', $id));
    }
}
