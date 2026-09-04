<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderDispatched;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.order.order.dispatched')]
final readonly class OrderDispatchedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $dispatchedAt,
    ) {
    }
}
