<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\IntegrationEvent\ErasureCancelled;

use Compliance\Erasing\Domain\Event\ErasureCancelled;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('compliance.erasing.publish_erasure_cancelled')]
final readonly class ErasureCancelledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ErasureCancelled::class)]
    public function __invoke(ErasureCancelled $event, Message $message): void
    {
        $this->publisher->publish(new ErasureCancelledIntegrationEvent(
            identityId: $event->identityId,
            cancelledAt: $event->cancelledAt,
        ), $message);
    }
}
