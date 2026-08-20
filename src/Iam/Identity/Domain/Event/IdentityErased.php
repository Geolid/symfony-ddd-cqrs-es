<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('iam.identity.identity.erased')]
final readonly class IdentityErased implements DomainEventInterface, DataSubjectErasureInterface
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
