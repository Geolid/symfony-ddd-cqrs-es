<?php

declare(strict_types=1);

namespace Iam\Access\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.access.granted')]
final readonly class PermissionGranted implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $permission,
        public string $grantedAt,
    ) {
    }
}
