<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Event;

use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasing.erasure.cancelled')]
final readonly class ErasureCancelled
{
    public function __construct(
        public ErasureId $id,
        public string $identityId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
