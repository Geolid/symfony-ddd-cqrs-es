<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('sales.customer.customer.erased')]
final readonly class CustomerErased implements DomainEventInterface, DataSubjectErasureInterface
{
    public function __construct(
        public string $id,
        public string $erasedAt,
    ) {
    }

    public function subjectId(): string
    {
        return $this->id;
    }
}
