<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\IntegrationEvent\ErasureApproved;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Event\ErasureApproved;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('compliance.erasing.publish_erasure_approved')]
final readonly class ErasureApprovedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ErasureApproved::class)]
    public function __invoke(ErasureApproved $event): void
    {
        $this->publisher->publish(Erasure::class, $event->id, new ErasureApprovedIntegrationEvent(
            identityId: $event->identityId,
            approvedAt: $event->approvedAt,
        ));
    }
}
