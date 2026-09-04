<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Finder\Product;

use Catalog\Listing\Application\Exception\ProductResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;
use Shared\Application\Finder\PaginatableFinderInterface;

/**
 * @extends IterableFinderInterface<ProductResult>
 * @extends PaginatableFinderInterface<ProductResult>
 */
interface ProductFinderInterface extends IterableFinderInterface, PaginatableFinderInterface
{
    /**
     * @throws ProductResultNotFoundException
     */
    public function ofId(string $id): ProductResult;
}
