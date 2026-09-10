<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CartLine;

final readonly class CartLineResult
{
    public function __construct(
        public string $lineId,
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
        public int $quantity,
    ) {
    }
}
