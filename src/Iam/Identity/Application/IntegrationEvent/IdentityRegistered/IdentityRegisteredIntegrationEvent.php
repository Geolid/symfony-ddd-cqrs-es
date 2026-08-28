<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityRegistered;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.iam.identity.identity.registered')]
final readonly class IdentityRegisteredIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $registeredAt,
    ) {
    }
}
