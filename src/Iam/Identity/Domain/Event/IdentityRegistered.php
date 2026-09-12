<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.identity.identity.registered')]
final readonly class IdentityRegistered
{
    public function __construct(
        public IdentityId $id,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
