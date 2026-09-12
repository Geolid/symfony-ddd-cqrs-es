<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Event;

use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('compliance.erasing.erasure.approved')]
final readonly class ErasureApproved
{
    public function __construct(
        public ErasureId $id,
        public string $identityId,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
