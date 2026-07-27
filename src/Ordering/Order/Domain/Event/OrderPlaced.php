<?php

declare(strict_types=1);

namespace Ordering\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('ordering.order.placed')]
final readonly class OrderPlaced implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $customerId,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
