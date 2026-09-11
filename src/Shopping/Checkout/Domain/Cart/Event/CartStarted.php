<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.cart.started')]
final readonly class CartStarted
{
    public function __construct(
        public string $id,
        public string $shopperId,
        public \DateTimeImmutable $startedAt,
    ) {
    }
}
