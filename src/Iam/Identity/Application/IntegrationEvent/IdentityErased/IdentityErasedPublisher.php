<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityErased;

use Iam\Identity\Domain\Event\IdentityErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_erased')]
final readonly class IdentityErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event, Message $message): void
    {
        $this->publisher->publish(new IdentityErasedIntegrationEvent(
            identityId: $event->id->toString(),
            erasedAt: $event->erasedAt,
        ), $message);
    }
}
