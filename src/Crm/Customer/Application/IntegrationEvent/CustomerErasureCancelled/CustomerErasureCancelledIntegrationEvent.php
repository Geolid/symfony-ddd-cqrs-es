<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.crm.customer.customer.erasure_cancelled')]
final readonly class CustomerErasureCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $customerId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
