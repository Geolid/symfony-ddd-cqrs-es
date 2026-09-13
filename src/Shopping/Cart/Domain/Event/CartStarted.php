<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shopping\Cart\Domain\ValueObject\CartId;

#[Event('shopping.cart.cart.started')]
final readonly class CartStarted
{
    public function __construct(
        public CartId $id,
        public string $customerId,
        public \DateTimeImmutable $startedAt,
    ) {
    }
}
