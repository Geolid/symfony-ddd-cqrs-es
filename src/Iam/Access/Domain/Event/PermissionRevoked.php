<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.access.permission_revoked')]
final readonly class PermissionRevoked implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $revokedAt,
    ) {
    }
}
