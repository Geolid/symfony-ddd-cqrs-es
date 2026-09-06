<?php

declare(strict_types=1);

namespace Finance\Refund\Application\IntegrationEvent\RefundInitiated;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.refund.refund.initiated')]
final readonly class RefundInitiatedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $refundId,
        public string $paymentId,
        public string $orderId,
        public int $amountInCents,
        public \DateTimeImmutable $initiatedAt,
    ) {
    }
}
