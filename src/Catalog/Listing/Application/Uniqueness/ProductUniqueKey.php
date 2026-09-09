<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Uniqueness;

enum ProductUniqueKey: string
{
    case LABEL = 'catalog.listing.product.label';
}
