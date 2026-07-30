<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('sales.order.integration.cancelled')]
final readonly class OrderCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $cancelledAt,
    ) {
    }
}
