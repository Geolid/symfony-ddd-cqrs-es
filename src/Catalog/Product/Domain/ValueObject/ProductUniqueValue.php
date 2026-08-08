<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\ValueObject;

enum ProductUniqueValue: string
{
    case LABEL = 'catalog.product.label';
}
