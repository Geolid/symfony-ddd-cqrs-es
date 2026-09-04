<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\ValueObject;

enum ProductUniqueKey: string
{
    case LABEL = 'catalog.listing.product.label';
}
