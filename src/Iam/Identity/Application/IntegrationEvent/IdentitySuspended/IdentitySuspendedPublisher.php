<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentitySuspended;

use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.identity_suspended_publisher')]
final readonly class IdentitySuspendedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentitySuspended::class)]
    public function __invoke(IdentitySuspended $event): void
    {
        $this->publisher->publish(Identity::class, $event->id, new IdentitySuspendedIntegrationEvent(
            identityId: $event->id,
            suspendedAt: $event->suspendedAt,
        ));
    }
}
