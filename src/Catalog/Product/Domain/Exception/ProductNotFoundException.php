<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Exception;

use Catalog\Product\Domain\ValueObject\ProductId;

final class ProductNotFoundException extends \DomainException
{
    public static function forId(ProductId $id): self
    {
        return new self(\sprintf('Product with ID "%s" does not exist.', $id->toString()));
    }
}
