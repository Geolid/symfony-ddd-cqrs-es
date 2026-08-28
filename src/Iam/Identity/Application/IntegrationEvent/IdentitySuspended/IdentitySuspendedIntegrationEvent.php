<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentitySuspended;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.iam.identity.identity.suspended')]
final readonly class IdentitySuspendedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $suspendedAt,
    ) {
    }
}
