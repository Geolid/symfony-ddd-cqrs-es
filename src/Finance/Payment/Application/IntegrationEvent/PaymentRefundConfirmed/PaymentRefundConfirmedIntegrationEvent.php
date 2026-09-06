<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundConfirmed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.refund_confirmed')]
final readonly class PaymentRefundConfirmedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $refundId,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
