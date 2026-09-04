<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentCaptured;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.captured')]
final readonly class PaymentCapturedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $capturedAt,
    ) {
    }
}
