<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('sales.customer.integration.registered')]
final readonly class CustomerRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $customerId,
        #[PersonalData(fallback: 'erased@erased.invalid')]
        public string $email,
        public string $registeredAt,
    ) {
    }
}
