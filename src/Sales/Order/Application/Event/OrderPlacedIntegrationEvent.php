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
    /**
     * @param list<array{label: string, quantity: int, unitAmountInCents: int}> $lines
     */
    public function __construct(
        public string $orderId,
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: null)]
        public ?string $buyerAddress,
        public array $lines,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
