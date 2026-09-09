<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.cart.converted')]
final readonly class CartConverted
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $convertedAt,
    ) {
    }
}
