<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityErased;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('iam.identity.identity.integration.erased')]
final readonly class IdentityErasedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public string $erasedAt,
    ) {
    }
}
