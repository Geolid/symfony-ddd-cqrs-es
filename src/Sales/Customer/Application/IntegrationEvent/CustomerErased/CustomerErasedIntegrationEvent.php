<?php

declare(strict_types=1);

namespace Sales\Customer\Application\IntegrationEvent\CustomerErased;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('sales.customer.integration.erased')]
final readonly class CustomerErasedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $customerId,
        public string $erasedAt,
    ) {
    }
}
