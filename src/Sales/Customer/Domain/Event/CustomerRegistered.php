<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.customer.registered')]
final readonly class CustomerRegistered implements DomainEventInterface
{
    public function __construct(
        #[DataSubjectId]
        public string $id,
        #[PersonalData(fallback: 'erased@erased.invalid')]
        public string $email,
        public string $registeredAt,
    ) {
    }
}
