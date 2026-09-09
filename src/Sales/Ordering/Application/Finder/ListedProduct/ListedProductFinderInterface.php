<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\ListedProduct;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<ListedProductResult>
 */
interface ListedProductFinderInterface extends IterableFinderInterface
{
    public function byIds(string ...$productIds): static;
}
