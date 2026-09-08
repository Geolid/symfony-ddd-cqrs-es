<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.identity.identity.erasure_cancelled')]
final readonly class IdentityErasureCancelled
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
