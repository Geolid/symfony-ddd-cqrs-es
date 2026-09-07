<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderAborted;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.order.order.aborted')]
final readonly class OrderAbortedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $buyerId,
        public \DateTimeImmutable $abortedAt,
    ) {
    }
}
