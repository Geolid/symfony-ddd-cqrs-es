<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order.returned')]
final readonly class OrderReturned
{
    public function __construct(
        public string $id,
        public string $returnedAt,
    ) {
    }
}
