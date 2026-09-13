<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Cart\Domain\ValueObject\CartId;

#[Event('shopping.cart.cart.purchased')]
final readonly class CartPurchased
{
    public function __construct(
        public CartId $id,
        public \DateTimeImmutable $purchasedAt,
    ) {
    }
}
