<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\IntegrationEvent\SubjectErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.compliance.erasure.subject.erasure_cancelled')]
final readonly class SubjectErasureCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $subjectId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
