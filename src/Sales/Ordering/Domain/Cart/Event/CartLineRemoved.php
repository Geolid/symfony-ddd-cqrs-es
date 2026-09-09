<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.cart.line_removed')]
final readonly class CartLineRemoved
{
    public function __construct(
        public string $id,
        public string $lineId,
        public \DateTimeImmutable $removedAt,
    ) {
    }
}
