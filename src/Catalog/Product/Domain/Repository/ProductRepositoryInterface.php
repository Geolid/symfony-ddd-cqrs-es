<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\Repository;

use Catalog\Product\Domain\Product;
use Catalog\Product\Domain\ValueObject\ProductId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface ProductRepositoryInterface
{
    public function has(ProductId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(ProductId $id): Product;

    public function save(Product $product): void;
}
