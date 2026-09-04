<?php

declare(strict_types=1);

namespace Finance\Payer\Application\IntegrationEvent\PayerRegistered;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payer.payer.registered')]
final readonly class PayerRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $payerId,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
