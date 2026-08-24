<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('sales.order.order.anonymized')]
final readonly class OrderAnonymized implements DomainEventInterface, DataSubjectErasureInterface
{
    public function __construct(
        public string $id,
        public string $anonymizedAt,
    ) {
    }

    public function subjectId(): string
    {
        return $this->id;
    }
}
