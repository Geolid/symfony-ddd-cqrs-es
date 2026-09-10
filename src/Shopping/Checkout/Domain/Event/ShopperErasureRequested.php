<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.shopper.erasure_requested')]
final readonly class ShopperErasureRequested
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
