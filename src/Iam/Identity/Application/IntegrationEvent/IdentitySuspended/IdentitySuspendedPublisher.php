<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentitySuspended;

use Iam\Identity\Domain\Event\IdentitySuspended;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_suspended')]
final readonly class IdentitySuspendedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentitySuspended::class)]
    public function __invoke(IdentitySuspended $event, Message $message): void
    {
        $this->publisher->publish(new IdentitySuspendedIntegrationEvent(
            identityId: $event->id->toString(),
            suspendedAt: $event->suspendedAt,
        ), $message);
    }
}
