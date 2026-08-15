<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Translator;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Application\Event\IdentityReactivatedIntegrationEvent;
use Iam\Identity\Application\Event\IdentitySuspendedIntegrationEvent;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Event\IdentityReactivated;
use Iam\Identity\Domain\Event\IdentitySuspended;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('iam.identity.integration')]
final readonly class IdentityIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    #[Subscribe(IdentityErased::class)]
    public function onIdentityErased(IdentityErased $event): void
    {
        $this->append(
            IntegrationStreamId::build('iam.identity', $event->id),
            new IdentityErasedIntegrationEvent(
                identityId: $event->id,
                erasedAt: $event->erasedAt,
            ),
        );
    }

    #[Subscribe(IdentitySuspended::class)]
    public function onIdentitySuspended(IdentitySuspended $event): void
    {
        $this->append(
            IntegrationStreamId::build('iam.identity', $event->id),
            new IdentitySuspendedIntegrationEvent(
                identityId: $event->id,
                suspendedAt: $event->suspendedAt,
            ),
        );
    }

    #[Subscribe(IdentityReactivated::class)]
    public function onIdentityReactivated(IdentityReactivated $event): void
    {
        $this->append(
            IntegrationStreamId::build('iam.identity', $event->id),
            new IdentityReactivatedIntegrationEvent(
                identityId: $event->id,
                reactivatedAt: $event->reactivatedAt,
            ),
        );
    }
}
