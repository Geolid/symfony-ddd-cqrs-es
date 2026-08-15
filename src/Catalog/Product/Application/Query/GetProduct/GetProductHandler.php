<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Query\GetProduct;

use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
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
