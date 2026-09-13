<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Finder\ListedProduct;

final readonly class ListedProductResult
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
    ) {
    }
}
