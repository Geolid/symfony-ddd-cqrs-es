<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\IntegrationEvent\SubjectErased;

use Compliance\Erasure\Domain\Event\SubjectErased;
use Compliance\Erasure\Domain\Subject;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('compliance.erasure.publish_subject_erased')]
final readonly class SubjectErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(SubjectErased::class)]
    public function __invoke(SubjectErased $event): void
    {
        $this->publisher->publish(Subject::class, $event->id, new SubjectErasedIntegrationEvent(
            subjectId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
