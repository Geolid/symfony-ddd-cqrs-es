<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CartItem;

final readonly class CartItemResult
{
    public function __construct(
        public string $cartId,
        public string $productId,
        public int $quantity,
    ) {
    }
}
