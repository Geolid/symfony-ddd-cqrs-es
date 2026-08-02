<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Query\ListProducts;

use Catalog\Product\Application\Finder\Product\ProductResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;

/**
 * @implements QueryInterface<ListResult<ProductResult>>
 */
final readonly class ListProducts implements QueryInterface
{
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
