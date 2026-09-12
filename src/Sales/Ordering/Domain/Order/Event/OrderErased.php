<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;

#[Event('sales.ordering.order.erased')]
final readonly class OrderErased
{
    public function __construct(
        public OrderId $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
