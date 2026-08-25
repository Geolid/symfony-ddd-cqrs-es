<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderReturnRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.order.order.return_requested')]
final readonly class OrderReturnRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $requestedAt,
    ) {
    }
}
