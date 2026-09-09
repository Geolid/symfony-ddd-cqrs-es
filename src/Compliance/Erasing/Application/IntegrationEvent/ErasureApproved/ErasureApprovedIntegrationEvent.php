<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\IntegrationEvent\ErasureApproved;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.compliance.erasing.erasure.approved')]
final readonly class ErasureApprovedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $identityId,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
