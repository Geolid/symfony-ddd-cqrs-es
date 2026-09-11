<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CheckoutSessionLine;

final readonly class CheckoutSessionLineResult
{
    public function __construct(
        public string $lineId,
        public string $checkoutSessionId,
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
        public int $quantity,
    ) {
    }
}
