<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('sales.order.integration.placed')]
final readonly class OrderPlacedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<array{label: string, quantity: int, unitAmountInCents: int}> $lines
     */
    public function __construct(
        public string $orderId,
        public string $customerId,
        public array $lines,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
