<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Finder\Cart;

final readonly class CartResult
{
    public function __construct(
        public string $id,
        public string $customerId,
    ) {
    }
}
