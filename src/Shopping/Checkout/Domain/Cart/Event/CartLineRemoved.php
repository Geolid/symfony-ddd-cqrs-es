<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.cart.line_removed')]
final readonly class CartLineRemoved
{
    public function __construct(
        public string $id,
        public string $lineId,
        public \DateTimeImmutable $removedAt,
    ) {
    }
}
