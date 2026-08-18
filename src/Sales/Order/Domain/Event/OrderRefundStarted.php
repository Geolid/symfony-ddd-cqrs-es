<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.order.refund_started')]
final readonly class OrderRefundStarted implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $startedAt,
    ) {
    }
}
