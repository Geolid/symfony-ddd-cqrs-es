<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Finder\Product;

use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<ProductResult>
 */
interface ProductFinderInterface extends PaginatedCollectionFinderInterface
{
    /**
     * @throws ResultNotFoundException
     */
    public function ofId(string $id): ProductResult;

    public function withoutDelisted(): static;

    public function sortedByLabel(): static;
}
