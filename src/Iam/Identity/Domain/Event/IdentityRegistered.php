<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.identity.identity.registered')]
final readonly class IdentityRegistered
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
