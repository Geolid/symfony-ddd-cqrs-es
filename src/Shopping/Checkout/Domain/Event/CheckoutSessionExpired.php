<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.checkout_session.expired')]
final readonly class CheckoutSessionExpired
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $expiredAt,
    ) {
    }
}
