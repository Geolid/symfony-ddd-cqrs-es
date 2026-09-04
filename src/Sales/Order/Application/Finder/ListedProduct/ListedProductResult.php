<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\ListedProduct;

final readonly class ListedProductResult
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
    ) {
    }
}
