<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.cart.checkout_abandoned')]
final readonly class CartCheckoutAbandoned
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $abandonedAt,
    ) {
    }
}
