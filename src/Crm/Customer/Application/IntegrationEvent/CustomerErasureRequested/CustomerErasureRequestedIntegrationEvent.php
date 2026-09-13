<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerErasureRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.crm.customer.customer.erasure_requested')]
final readonly class CustomerErasureRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $customerId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
