<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSession;

final readonly class CheckoutSessionItemResult
{
    public function __construct(
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
        public int $quantity,
    ) {
    }
}
