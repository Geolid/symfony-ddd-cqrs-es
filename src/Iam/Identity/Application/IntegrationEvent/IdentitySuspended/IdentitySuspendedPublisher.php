<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentitySuspended;

use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_suspended')]
final readonly class IdentitySuspendedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentitySuspended::class)]
    public function __invoke(IdentitySuspended $event): void
    {
        $this->publisher->publish(Identity::class, $event->id->toString(), new IdentitySuspendedIntegrationEvent(
            identityId: $event->id->toString(),
            suspendedAt: $event->suspendedAt,
        ));
    }
}
