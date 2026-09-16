<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\Finder\Cart;

use Shopping\Cart\Application\CartStatus;

final readonly class CartResult
{
    public function __construct(
        public string $id,
        public string $customerId,
        public CartStatus $status,
        public \DateTimeImmutable $startedAt,
    ) {
    }
}
