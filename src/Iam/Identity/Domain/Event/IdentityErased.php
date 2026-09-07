<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.identity.identity.erased')]
final readonly class IdentityErased
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
