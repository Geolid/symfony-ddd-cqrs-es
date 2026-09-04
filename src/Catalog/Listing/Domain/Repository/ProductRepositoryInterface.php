<?php

declare(strict_types=1);

namespace Catalog\Listing\Domain\Repository;

use Catalog\Listing\Domain\Exception\ProductAlreadyExistsException;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\ValueObject\ProductId;

interface ProductRepositoryInterface
{
    public function has(ProductId $id): bool;

    /**
     * @throws ProductNotFoundException
     */
    public function load(ProductId $id): Product;

    /**
     * @throws ProductAlreadyExistsException
     */
    public function save(Product $product): void;
}
