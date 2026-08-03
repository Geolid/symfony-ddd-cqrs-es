<?php

declare(strict_types=1);

namespace Sales\Order\Application\Product;

final readonly class Product
{
    public function __construct(
        public string $id,
        public string $label,
        public int $unitAmountInCents,
    ) {
    }
}
