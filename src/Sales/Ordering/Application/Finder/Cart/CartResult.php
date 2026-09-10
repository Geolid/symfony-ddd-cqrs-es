<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Cart;

use Sales\Ordering\Application\CartStatus;

final readonly class CartResult
{
    /**
     * @param list<CartLineResult> $lineItems
     */
    public function __construct(
        public string $id,
        public string $buyerId,
        public CartStatus $status,
        public array $lineItems,
    ) {
    }
}
