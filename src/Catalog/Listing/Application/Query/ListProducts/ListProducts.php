<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Query\ListProducts;

use Catalog\Listing\Application\Finder\Product\ProductResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\PaginatedResult;

/**
 * @implements QueryInterface<PaginatedResult<ProductResult>>
 */
final readonly class ListProducts implements QueryInterface
{
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
