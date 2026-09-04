<?php

declare(strict_types=1);

namespace Finance\Payer\Application\IntegrationEvent\PayerErased;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.finance.payer.payer.erased')]
final readonly class PayerErasedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $payerId,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
