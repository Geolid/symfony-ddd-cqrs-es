<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Finder\CartItem;

final readonly class CartItemResult
{
    public function __construct(
        public string $cartId,
        public string $productId,
        public int $quantity,
    ) {
    }
}
