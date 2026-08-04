<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Exception;

use Catalog\Product\Domain\ValueObject\ProductId;

final class ProductAlreadyDelistedException extends \DomainException
{
    public static function forId(ProductId $id): self
    {
        return new self(\sprintf('The product "%s" is already delisted.', $id->toString()));
    }
}
