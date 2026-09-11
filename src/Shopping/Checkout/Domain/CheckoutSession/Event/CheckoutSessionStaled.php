<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.checkout_session.staled')]
final readonly class CheckoutSessionStaled
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $staledAt,
    ) {
    }
}
