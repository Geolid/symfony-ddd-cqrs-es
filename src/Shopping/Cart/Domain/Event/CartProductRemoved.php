<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Cart\Domain\ValueObject\CartId;

#[Event('shopping.cart.cart.product_removed')]
final readonly class CartProductRemoved
{
    public function __construct(
        public CartId $id,
        public string $productId,
        public \DateTimeImmutable $removedAt,
    ) {
    }
}
