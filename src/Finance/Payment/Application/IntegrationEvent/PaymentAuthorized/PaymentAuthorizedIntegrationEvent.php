<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentAuthorized;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payment.payment.authorized')]
final readonly class PaymentAuthorizedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public \DateTimeImmutable $authorizedAt,
    ) {
    }
}
