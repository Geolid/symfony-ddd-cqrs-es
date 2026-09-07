<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\IntegrationEvent\SubjectErasureCancelled;

use Compliance\Erasure\Domain\Event\SubjectErasureCancelled;
use Compliance\Erasure\Domain\Subject;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('compliance.erasure.publish_subject_erasure_cancelled')]
final readonly class SubjectErasureCancelledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(SubjectErasureCancelled::class)]
    public function __invoke(SubjectErasureCancelled $event): void
    {
        $this->publisher->publish(Subject::class, $event->id, new SubjectErasureCancelledIntegrationEvent(
            subjectId: $event->id,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
