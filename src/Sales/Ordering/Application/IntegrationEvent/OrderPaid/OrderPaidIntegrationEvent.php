<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderPaid;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.ordering.order.paid')]
final readonly class OrderPaidIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $paidAt,
    ) {
    }
}
