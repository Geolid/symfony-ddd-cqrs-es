<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundRequired;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.refund_required')]
final readonly class PaymentRefundRequiredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $requiredAt,
    ) {
    }
}
