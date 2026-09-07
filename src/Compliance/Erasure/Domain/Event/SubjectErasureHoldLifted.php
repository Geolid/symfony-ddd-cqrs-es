<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Event;

use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasure.subject.erasure_hold_lifted')]
final readonly class SubjectErasureHoldLifted
{
    public function __construct(
        public string $id,
        public ErasureHoldReference $reference,
        public \DateTimeImmutable $liftedAt,
    ) {
    }
}
