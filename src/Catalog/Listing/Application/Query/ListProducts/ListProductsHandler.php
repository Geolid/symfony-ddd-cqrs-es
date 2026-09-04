<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Query\ListProducts;

use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Finder\Product\ProductResult;
use Shared\Application\Finder\PaginationMetadata;
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
        $paginator = $this->productFinder->paginate($query->page, $query->itemsPerPage);

        /** @var list<ProductResult> $items */
        $items = iterator_to_array($paginator);

        return new PaginatedResult($items, PaginationMetadata::fromPaginator($paginator));
    }
}
