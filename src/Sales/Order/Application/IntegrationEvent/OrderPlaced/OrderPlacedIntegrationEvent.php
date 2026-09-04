<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderPlaced;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.order.order.placed')]
final readonly class OrderPlacedIntegrationEvent implements IntegrationEventInterface
{
    /**
     * @param list<array{label: string, quantity: int, unitPriceInCents: int}> $lines
     */
    public function __construct(
        public string $orderId,
        public string $buyerId,
        public array $lines,
        public int $totalAmountInCents,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
