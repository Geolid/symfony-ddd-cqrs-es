<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order.delivered')]
final readonly class OrderDelivered
{
    public function __construct(
        public string $id,
        public string $deliveredAt,
    ) {
    }
}
