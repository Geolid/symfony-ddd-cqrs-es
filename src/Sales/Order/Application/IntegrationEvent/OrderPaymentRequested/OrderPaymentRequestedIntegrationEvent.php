<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderPaymentRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.sales.order.payment.requested')]
final readonly class OrderPaymentRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
