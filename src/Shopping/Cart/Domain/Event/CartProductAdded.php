<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\ValueObject\Quantity;
use Shopping\Cart\Domain\ValueObject\CartId;

#[Event('shopping.cart.cart.product_added')]
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
