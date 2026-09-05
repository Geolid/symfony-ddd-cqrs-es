<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order.prepared')]
final readonly class OrderPrepared
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $preparedAt,
    ) {
    }
}
