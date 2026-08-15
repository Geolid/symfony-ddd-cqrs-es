<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Repository;

use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ValueObject\ProductId;

interface ProductRepositoryInterface
{
    public function has(ProductId $id): bool;

    /**
     * @throws ProductNotFoundException
     */
    public function load(ProductId $id): Product;

    public function save(Product $product): void;
}
