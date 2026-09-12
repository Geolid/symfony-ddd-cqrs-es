<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Event;

use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('crm.customer.customer.erasure_cancelled')]
final readonly class CustomerErasureCancelled
{
    public function __construct(
        public CustomerId $id,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
