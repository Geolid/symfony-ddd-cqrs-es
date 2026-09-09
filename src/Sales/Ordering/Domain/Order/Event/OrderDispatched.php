<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.order.dispatched')]
final readonly class OrderDispatched
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $dispatchedAt,
    ) {
    }
}
