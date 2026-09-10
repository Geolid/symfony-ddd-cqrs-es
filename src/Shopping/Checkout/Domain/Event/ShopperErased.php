<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.shopper.erased')]
final readonly class ShopperErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
