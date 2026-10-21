<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityConfirmed;

use Iam\Identity\Domain\Event\IdentityConfirmed;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_confirmed')]
final readonly class IdentityConfirmedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityConfirmed::class)]
    public function __invoke(IdentityConfirmed $event, Message $message): void
    {
        $this->publisher->publish(new IdentityConfirmedIntegrationEvent(
            identityId: $event->id->toString(),
            confirmedAt: $event->confirmedAt,
        ), $message);
    }
}
