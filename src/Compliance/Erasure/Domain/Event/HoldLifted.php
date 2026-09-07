<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasure.subject.hold_lifted')]
final readonly class HoldLifted
{
    public function __construct(
        public string $id,
        public string $reference,
        public \DateTimeImmutable $liftedAt,
    ) {
    }
}
