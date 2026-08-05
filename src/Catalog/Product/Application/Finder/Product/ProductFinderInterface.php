<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Finder\Product;

use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<ProductResult>
 */
interface ProductFinderInterface extends PaginatedCollectionFinderInterface
{
    public function ofId(string $id): ?ProductResult;

    public function withoutDelisted(): static;
}
