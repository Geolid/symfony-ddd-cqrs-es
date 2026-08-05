<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('sales.order.integration.payment_requested')]
final readonly class OrderPaymentRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $requestedAt,
    ) {
    }
}
