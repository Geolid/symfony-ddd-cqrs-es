<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.cart.checkout_abandoned')]
final readonly class CartCheckoutAbandoned
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $abandonedAt,
    ) {
    }
}
