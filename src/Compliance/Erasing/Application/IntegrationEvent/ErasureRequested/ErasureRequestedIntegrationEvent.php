<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\IntegrationEvent\ErasureRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.compliance.erasing.erasure.requested')]
final readonly class ErasureRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
