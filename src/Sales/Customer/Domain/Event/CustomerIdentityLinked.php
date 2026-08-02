<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('sales.customer.identity_linked')]
final readonly class CustomerIdentityLinked implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
    ) {
    }
}
