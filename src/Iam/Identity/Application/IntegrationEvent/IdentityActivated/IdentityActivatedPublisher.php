<?php

declare(strict_types=1);

namespace Iam\Identity\Application\IntegrationEvent\IdentityActivated;

use Iam\Identity\Domain\Event\IdentityActivated;
use Iam\Identity\Domain\Identity;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('iam.identity.publish_identity_activated')]
final readonly class IdentityActivatedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(IdentityActivated::class)]
    public function __invoke(IdentityActivated $event): void
    {
        $this->publisher->publish(Identity::class, $event->id->toString(), new IdentityActivatedIntegrationEvent(
            identityId: $event->id->toString(),
            activatedAt: $event->activatedAt,
        ));
    }
}
