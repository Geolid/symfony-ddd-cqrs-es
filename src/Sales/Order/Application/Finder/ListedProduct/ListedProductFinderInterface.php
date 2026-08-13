<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\ListedProduct;

use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<ListedProductResult>
 */
interface ListedProductFinderInterface extends CollectionFinderInterface
{
    public function byIds(string ...$productIds): static;
}
