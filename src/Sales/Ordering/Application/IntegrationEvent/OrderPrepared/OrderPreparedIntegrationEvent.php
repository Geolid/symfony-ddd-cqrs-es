<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderPrepared;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.ordering.order.prepared')]
final readonly class OrderPreparedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $preparedAt,
    ) {
    }
}
