<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.order.dispatched')]
final readonly class OrderDispatched implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $dispatchedAt,
    ) {
    }
}
