<?php

declare(strict_types=1);

namespace Crm\Customer\Application\IntegrationEvent\CustomerRegistered;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.crm.customer.customer.registered')]
final readonly class CustomerRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
