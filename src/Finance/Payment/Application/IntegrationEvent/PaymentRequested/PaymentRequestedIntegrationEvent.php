<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.requested')]
final readonly class PaymentRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $paymentId,
        public string $orderId,
        public int $amountInCents,
        public string $reference,
        public string $checkoutUrl,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
