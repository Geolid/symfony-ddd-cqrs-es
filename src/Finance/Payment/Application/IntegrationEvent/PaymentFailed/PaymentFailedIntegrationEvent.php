<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentFailed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.failed')]
final readonly class PaymentFailedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $failedAt,
    ) {
    }
}
