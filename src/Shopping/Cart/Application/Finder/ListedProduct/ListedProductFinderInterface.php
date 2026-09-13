<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Finder\ListedProduct;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<ListedProductResult>
 */
interface ListedProductFinderInterface extends IterableFinderInterface
{
    public function byIds(string ...$productIds): static;
}
