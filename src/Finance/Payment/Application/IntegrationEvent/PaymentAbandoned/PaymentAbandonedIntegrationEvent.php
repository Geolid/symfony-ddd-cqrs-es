<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentAbandoned;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.abandoned')]
final readonly class PaymentAbandonedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $paymentId,
        public string $cartId,
        public \DateTimeImmutable $abandonedAt,
    ) {
    }
}
