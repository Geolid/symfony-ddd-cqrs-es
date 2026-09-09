<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;

#[Event('sales.ordering.cart.line_quantity_changed')]
final readonly class CartLineQuantityChanged
{
    public function __construct(
        public string $id,
        public string $lineId,
        public Quantity $quantity,
        public \DateTimeImmutable $changedAt,
    ) {
    }
}
