<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityRegistered;

use Iam\Identity\Domain\Event\IdentityRegistered;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_registered')]
final readonly class IdentityRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityRegistered::class)]
    public function __invoke(IdentityRegistered $event, Message $message): void
    {
        $this->publisher->publish(new IdentityRegisteredIntegrationEvent(
            identityId: $event->id->toString(),
            fullName: $event->fullName->value,
            email: $event->email->value,
            registeredAt: $event->registeredAt,
        ), $message);
    }
}
