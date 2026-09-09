<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.ordering.order.confirmed')]
final readonly class OrderConfirmed
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
