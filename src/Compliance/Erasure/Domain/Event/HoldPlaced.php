<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Event;

use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasure.subject.hold_placed')]
final readonly class HoldPlaced
{
    public function __construct(
        public string $id,
        public HoldReference $reference,
        public \DateTimeImmutable $placedAt,
    ) {
    }
}
