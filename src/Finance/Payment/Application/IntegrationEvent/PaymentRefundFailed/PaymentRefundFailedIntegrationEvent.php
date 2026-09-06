<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundFailed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.refund_failed')]
final readonly class PaymentRefundFailedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $refundId,
        public \DateTimeImmutable $failedAt,
    ) {
    }
}
