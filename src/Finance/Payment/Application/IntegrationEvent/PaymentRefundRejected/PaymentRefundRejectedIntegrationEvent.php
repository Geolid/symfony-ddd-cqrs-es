<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundRejected;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.refund_rejected')]
final readonly class PaymentRefundRejectedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
