<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Query\GetProduct;

use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Finder\Product\ProductResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetProductHandler
{
    public function __construct(private ProductFinderInterface $productFinder)
    {
    }

    /**
     * @throws ProductResultNotFoundException
     */
    public function __invoke(GetProduct $query): ProductResult
    {
        return $this->productFinder->ofId($query->id);
    }
}
