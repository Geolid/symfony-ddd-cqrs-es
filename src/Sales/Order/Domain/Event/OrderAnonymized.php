<?php

declare(strict_types=1);

namespace Sales\Order\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('sales.order.order.anonymized')]
final readonly class OrderAnonymized implements DataSubjectErasureInterface
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $anonymizedAt,
    ) {
    }

    public function subjectId(): string
    {
        return $this->id;
    }
}
