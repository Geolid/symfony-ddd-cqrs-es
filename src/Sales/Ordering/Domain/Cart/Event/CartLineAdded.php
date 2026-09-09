<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;

#[Event('sales.ordering.cart.line_added')]
final readonly class CartLineAdded
{
    public function __construct(
        public string $id,
        public string $lineId,
        public Product $product,
        public Quantity $quantity,
        public \DateTimeImmutable $addedAt,
    ) {
    }
}
