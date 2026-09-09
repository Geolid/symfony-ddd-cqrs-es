<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.order.prepared')]
final readonly class OrderPrepared
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $preparedAt,
    ) {
    }
}
