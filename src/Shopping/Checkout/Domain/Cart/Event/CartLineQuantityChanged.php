<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;

#[Event('shopping.checkout.cart.line_quantity_changed')]
final readonly class CartLineQuantityChanged
{
    public function __construct(
        public string $id,
        public string $lineId,
        public Quantity $quantity,
        public \DateTimeImmutable $changedAt,
    ) {
    }
}
