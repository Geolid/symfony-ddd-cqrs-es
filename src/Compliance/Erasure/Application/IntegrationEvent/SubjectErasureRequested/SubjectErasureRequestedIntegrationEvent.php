<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\IntegrationEvent\SubjectErasureRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.compliance.erasure.subject.erasure_requested')]
final readonly class SubjectErasureRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $subjectId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
