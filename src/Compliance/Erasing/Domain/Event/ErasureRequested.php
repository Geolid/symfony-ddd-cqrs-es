<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Event;

use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasing.erasure.requested')]
final readonly class ErasureRequested
{
    public function __construct(
        public ErasureId $id,
        public string $identityId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
