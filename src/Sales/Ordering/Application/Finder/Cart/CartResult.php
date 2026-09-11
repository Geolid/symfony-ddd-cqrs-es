<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Cart;

final readonly class CartResult
{
    public function __construct(
        public string $cartId,
        public string $shopperId,
    ) {
    }
}
