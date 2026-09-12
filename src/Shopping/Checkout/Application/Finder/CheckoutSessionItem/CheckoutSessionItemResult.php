<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSessionItem;

final readonly class CheckoutSessionItemResult
{
    public function __construct(
        public string $checkoutSessionId,
        public string $productId,
        public string $label,
        public int $unitPriceInCents,
        public int $quantity,
    ) {
    }
}
