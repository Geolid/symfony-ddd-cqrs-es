<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Query\ListProducts;

use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\ListResult;

#[QueryHandler]
final readonly class ListProductsHandler
{
    public function __construct(private ProductFinderInterface $productFinder)
    {
    }

    /**
     * @return ListResult<ProductResult>
     */
    public function __invoke(ListProducts $query): ListResult
    {
        $paginator = $this->productFinder->sortedByLabel()->paginate($query->page, $query->itemsPerPage);

        /** @var list<ProductResult> $items */
        $items = iterator_to_array($paginator);

        return new ListResult(
            $items,
            new PaginationInfo(
                totalItems: $paginator->totalItems(),
                currentPage: $paginator->currentPage(),
                itemsPerPage: $paginator->itemsPerPage(),
                lastPage: $paginator->lastPage(),
            ),
        );
    }
}
