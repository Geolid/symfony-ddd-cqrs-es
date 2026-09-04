<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerErased;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.buyer.buyer.erased')]
final readonly class BuyerErasedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $buyerId,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
