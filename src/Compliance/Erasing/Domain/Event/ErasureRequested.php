<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasing.erasure.requested')]
final readonly class ErasureRequested
{
    public function __construct(
        public string $id,
        public string $identityId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
