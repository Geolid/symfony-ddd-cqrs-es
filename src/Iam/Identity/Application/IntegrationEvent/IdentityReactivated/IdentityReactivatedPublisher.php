<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityReactivated;

use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_reactivated')]
final readonly class IdentityReactivatedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityReactivated::class)]
    public function __invoke(IdentityReactivated $event): void
    {
        $this->publisher->publish(Identity::class, $event->id, new IdentityReactivatedIntegrationEvent(
            identityId: $event->id,
            reactivatedAt: $event->reactivatedAt,
        ));
    }
}
