<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Translator;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use Iam\Identity\Domain\Event\IdentityErased;
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
}
