<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.checkout_session.consumed')]
final readonly class CheckoutSessionConsumed
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $consumedAt,
    ) {
    }
}
