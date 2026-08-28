<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Gdpr\DataSubjectErasureInterface;

#[Event('iam.identity.identity.erased')]
final readonly class IdentityErased implements DataSubjectErasureInterface
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }

    public function subjectId(): string
    {
        return $this->id;
    }
}
