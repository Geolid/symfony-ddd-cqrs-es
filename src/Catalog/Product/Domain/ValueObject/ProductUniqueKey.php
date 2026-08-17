<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\ValueObject;

enum ProductUniqueKey: string
{
    case LABEL = 'catalog.product.label';
}
