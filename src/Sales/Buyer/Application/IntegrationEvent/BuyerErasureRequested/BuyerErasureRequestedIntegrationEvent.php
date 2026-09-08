<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerErasureRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.buyer.buyer.erasure_requested')]
final readonly class BuyerErasureRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $buyerId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
