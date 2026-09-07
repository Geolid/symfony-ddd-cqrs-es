<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Event;

use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasure.subject.hold_lifted')]
final readonly class HoldLifted
{
    public function __construct(
        public string $id,
        public HoldReference $reference,
        public \DateTimeImmutable $liftedAt,
    ) {
    }
}
