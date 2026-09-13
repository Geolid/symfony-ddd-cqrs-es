<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

#[Event('shopping.checkout.cart.started')]
final readonly class CartStarted
{
    public function __construct(
        public CartId $id,
        public string $customerId,
        public \DateTimeImmutable $startedAt,
    ) {
    }
}
