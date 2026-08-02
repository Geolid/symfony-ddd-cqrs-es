<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.identity.reactivated')]
final readonly class IdentityReactivated implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $reactivatedAt,
    ) {
    }
}
