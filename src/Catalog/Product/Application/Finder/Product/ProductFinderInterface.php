<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Finder\Product;

use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;
use Shared\Application\Finder\PaginableFinderInterface;

/**
 * @extends CollectionFinderInterface<ProductResult>
 * @extends PaginableFinderInterface<ProductResult>
 */
interface ProductFinderInterface extends CollectionFinderInterface, PaginableFinderInterface
{
    /**
     * @throws ProductResultNotFoundException
     */
    public function ofId(string $id): ProductResult;

    public function sortedByLabel(): static;
}
