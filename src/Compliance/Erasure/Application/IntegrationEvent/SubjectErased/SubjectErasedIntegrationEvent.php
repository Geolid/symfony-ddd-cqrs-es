<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\IntegrationEvent\SubjectErased;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.compliance.erasure.subject.erased')]
final readonly class SubjectErasedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $subjectId,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
