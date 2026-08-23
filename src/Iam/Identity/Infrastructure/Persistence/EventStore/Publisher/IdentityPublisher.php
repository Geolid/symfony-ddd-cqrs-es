<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Publisher;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityRegisteredIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentityRegistered;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\EventStore\Publisher\IntegrationEventAppenderInterface;
use Shared\Infrastructure\Persistence\EventStore\Publisher\Publisher;

#[Publisher('iam.identity.integration')]
final readonly class IdentityPublisher
{
    public function __construct(private IntegrationEventAppenderInterface $appender)
    {
    }

    #[Subscribe(IdentityRegistered::class)]
    public function onIdentityRegistered(IdentityRegistered $event): void
    {
        $this->appender->append(Identity::class, $event->id, new IdentityRegisteredIntegrationEvent(
            identityId: $event->id,
            registeredAt: $event->registeredAt,
        ));
    }

    #[Subscribe(IdentityErased::class)]
    public function onIdentityErased(IdentityErased $event): void
    {
        $this->appender->append(Identity::class, $event->id, new IdentityErasedIntegrationEvent(
            identityId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }

    #[Subscribe(IdentitySuspended::class)]
    public function onIdentitySuspended(IdentitySuspended $event): void
    {
        $this->appender->append(Identity::class, $event->id, new IdentitySuspendedIntegrationEvent(
            identityId: $event->id,
            suspendedAt: $event->suspendedAt,
        ));
    }

    #[Subscribe(IdentityReactivated::class)]
    public function onIdentityReactivated(IdentityReactivated $event): void
    {
        $this->appender->append(Identity::class, $event->id, new IdentityReactivatedIntegrationEvent(
            identityId: $event->id,
            reactivatedAt: $event->reactivatedAt,
        ));
    }
}
