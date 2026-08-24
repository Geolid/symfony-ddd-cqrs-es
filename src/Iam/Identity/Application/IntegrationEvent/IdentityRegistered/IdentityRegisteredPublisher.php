<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityRegistered;

use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.identity_registered_publisher')]
final readonly class IdentityRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityRegistered::class)]
    public function __invoke(IdentityRegistered $event): void
    {
        $this->publisher->publish(Identity::class, $event->id, new IdentityRegisteredIntegrationEvent(
            identityId: $event->id,
            registeredAt: $event->registeredAt,
        ));
    }
}
