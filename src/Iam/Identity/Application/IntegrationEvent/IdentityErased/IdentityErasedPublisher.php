<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityErased;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.identity_erased_publisher')]
final readonly class IdentityErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event): void
    {
        $this->publisher->publish(Identity::class, $event->id, new IdentityErasedIntegrationEvent(
            identityId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
