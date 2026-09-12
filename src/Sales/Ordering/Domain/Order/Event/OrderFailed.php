<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Order\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;

#[Event('sales.ordering.order.failed')]
final readonly class OrderFailed
{
    public function __construct(
        public OrderId $id,
        public \DateTimeImmutable $failedAt,
    ) {
    }
}
