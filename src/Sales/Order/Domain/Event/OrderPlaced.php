<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.order.placed')]
final readonly class OrderPlaced implements DomainEventInterface
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: null)]
        public ?string $buyerAddress,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
