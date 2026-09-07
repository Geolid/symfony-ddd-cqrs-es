<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\IntegrationEvent\SubjectErasureRequested;

use Compliance\Erasure\Domain\Event\SubjectErasureRequested;
use Compliance\Erasure\Domain\Subject;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('compliance.erasure.publish_subject_erasure_requested')]
final readonly class SubjectErasureRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(SubjectErasureRequested::class)]
    public function __invoke(SubjectErasureRequested $event): void
    {
        $this->publisher->publish(Subject::class, $event->id, new SubjectErasureRequestedIntegrationEvent(
            subjectId: $event->id,
            requestedAt: $event->requestedAt,
        ));
    }
}
