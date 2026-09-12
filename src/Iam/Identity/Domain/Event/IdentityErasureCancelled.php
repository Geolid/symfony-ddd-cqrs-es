<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.identity.identity.erasure_cancelled')]
final readonly class IdentityErasureCancelled
{
    public function __construct(
        public IdentityId $id,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
