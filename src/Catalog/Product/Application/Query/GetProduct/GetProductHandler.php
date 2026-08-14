<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Query\GetProduct;

use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetProductHandler
{
    public function __construct(private ProductFinderInterface $productFinder)
    {
    }

    /**
     * @throws ResultNotFoundException
     */
    public function __invoke(GetProduct $query): ProductResult
    {
        return $this->productFinder->ofId($query->id);
    }
}
