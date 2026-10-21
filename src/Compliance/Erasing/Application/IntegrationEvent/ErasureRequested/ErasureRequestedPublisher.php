<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\IntegrationEvent\ErasureRequested;

use Compliance\Erasing\Domain\Event\ErasureRequested;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('compliance.erasing.publish_erasure_requested')]
final readonly class ErasureRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ErasureRequested::class)]
    public function __invoke(ErasureRequested $event, Message $message): void
    {
        $this->publisher->publish(new ErasureRequestedIntegrationEvent(
            identityId: $event->identityId,
            requestedAt: $event->requestedAt,
            effectiveAt: $event->effectiveAt,
        ), $message);
    }
}
