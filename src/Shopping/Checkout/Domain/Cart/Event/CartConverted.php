<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('shopping.checkout.cart.converted')]
final readonly class CartConverted
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $convertedAt,
    ) {
    }
}
