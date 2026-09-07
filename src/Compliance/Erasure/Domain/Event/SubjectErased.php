<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasure.subject.erased')]
final readonly class SubjectErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
