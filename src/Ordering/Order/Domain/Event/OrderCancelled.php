<?php

declare(strict_types=1);

namespace Ordering\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('ordering.order.cancelled')]
final readonly class OrderCancelled implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $cancelledAt,
    ) {
    }
}
