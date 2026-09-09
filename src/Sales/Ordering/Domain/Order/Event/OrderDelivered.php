<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.order.delivered')]
final readonly class OrderDelivered
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $deliveredAt,
    ) {
    }
}
