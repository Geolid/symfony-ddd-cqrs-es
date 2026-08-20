<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('iam.identity.identity.integration.suspended')]
final readonly class IdentitySuspendedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public string $suspendedAt,
    ) {
    }
}
