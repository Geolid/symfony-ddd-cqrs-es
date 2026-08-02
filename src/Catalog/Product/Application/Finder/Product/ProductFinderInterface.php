<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Finder\Product;

use Shared\Application\Finder\PaginatedFinderInterface;

/**
 * @extends PaginatedFinderInterface<ProductResult>
 */
interface ProductFinderInterface extends PaginatedFinderInterface
{
    public function withoutDelisted(): static;
}
