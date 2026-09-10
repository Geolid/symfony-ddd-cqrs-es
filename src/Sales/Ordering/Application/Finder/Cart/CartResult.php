<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Cart;

use Sales\Ordering\Application\CartStatus;

final readonly class CartResult
{
    public function __construct(
        public string $id,
        public string $buyerId,
        public CartStatus $status,
    ) {
    }
}
