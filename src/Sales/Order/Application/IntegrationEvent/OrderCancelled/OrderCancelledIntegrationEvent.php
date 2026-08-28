<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderCancelled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.order.order.cancelled')]
final readonly class OrderCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
