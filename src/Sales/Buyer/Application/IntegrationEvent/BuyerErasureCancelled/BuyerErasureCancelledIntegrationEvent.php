<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.buyer.buyer.erasure_cancelled')]
final readonly class BuyerErasureCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $buyerId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
