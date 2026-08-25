<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Finder\Product;

final readonly class ProductResult
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
    ) {
    }
}
