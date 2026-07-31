<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('sales.order.integration.placed')]
final readonly class OrderPlacedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: null)]
        public ?string $buyerAddress,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
