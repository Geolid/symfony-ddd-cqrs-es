<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Query\ListProducts;

use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Shared\Application\Query\Pagination;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\PaginatedResult;

#[QueryHandler]
final readonly class ListProductsHandler
{
    public function __construct(private ProductFinderInterface $productFinder)
    {
    }

    /**
     * @return PaginatedResult<ProductResult>
     */
    public function __invoke(ListProducts $query): PaginatedResult
    {
        $paginator = $this->productFinder->sortedByLabel()->paginate($query->page, $query->itemsPerPage);

        /** @var list<ProductResult> $items */
        $items = iterator_to_array($paginator);

        return new PaginatedResult($items, Pagination::fromPaginator($paginator));
    }
}
