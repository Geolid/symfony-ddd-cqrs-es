<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.order.erased')]
final readonly class OrderErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
