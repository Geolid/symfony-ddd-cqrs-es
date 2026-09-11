<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[Event('shopping.checkout.cart.line_added')]
final readonly class CartLineAdded
{
    public function __construct(
        public string $id,
        public string $lineId,
        public Product $product,
        public Quantity $quantity,
        public \DateTimeImmutable $addedAt,
    ) {
    }
}
