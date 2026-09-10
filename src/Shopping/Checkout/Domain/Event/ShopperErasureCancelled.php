<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.shopper.erasure_cancelled')]
final readonly class ShopperErasureCancelled
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
