<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityConfirmed;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.iam.identity.identity.confirmed')]
final readonly class IdentityConfirmedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $confirmedAt,
    ) {
    }
}
