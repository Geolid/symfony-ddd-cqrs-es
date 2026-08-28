<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityReactivated;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.iam.identity.identity.reactivated')]
final readonly class IdentityReactivatedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $reactivatedAt,
    ) {
    }
}
