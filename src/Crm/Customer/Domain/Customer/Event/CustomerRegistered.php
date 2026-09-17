<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Event;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;

#[Event('crm.customer.customer.registered')]
final readonly class CustomerRegistered
{
    public function __construct(
        #[DataSubjectId]
        public CustomerId $id,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
