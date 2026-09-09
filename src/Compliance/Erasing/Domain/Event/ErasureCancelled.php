<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasing.erasure.cancelled')]
final readonly class ErasureCancelled
{
    public function __construct(
        public string $id,
        public string $identityId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
