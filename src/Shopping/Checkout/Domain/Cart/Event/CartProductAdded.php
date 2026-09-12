<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[Event('shopping.checkout.cart.product_added')]
final readonly class CartProductAdded
{
    public function __construct(
        public CartId $id,
        public string $productId,
        public Quantity $quantity,
        public \DateTimeImmutable $addedAt,
    ) {
    }
}
