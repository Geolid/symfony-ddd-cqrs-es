<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

#[Event('iam.identity.identity.integration.reactivated')]
final readonly class IdentityReactivatedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public string $reactivatedAt,
    ) {
    }
}
