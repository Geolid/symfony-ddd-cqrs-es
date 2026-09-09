<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.compliance.erasing.erasure.cancelled')]
final readonly class ErasureCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
