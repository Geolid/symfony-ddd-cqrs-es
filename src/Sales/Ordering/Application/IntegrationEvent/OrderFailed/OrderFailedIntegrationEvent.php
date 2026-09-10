<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderFailed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.ordering.order.failed')]
final readonly class OrderFailedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $shopperId,
        public \DateTimeImmutable $failedAt,
    ) {
    }
}
