<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('sales.order.order.disputed')]
final readonly class OrderDisputed
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $disputedAt,
    ) {
    }
}
