<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

#[Event('shopping.checkout.cart.purchased')]
final readonly class CartPurchased
{
    public function __construct(
        public CartId $id,
        public \DateTimeImmutable $purchasedAt,
    ) {
    }
}
