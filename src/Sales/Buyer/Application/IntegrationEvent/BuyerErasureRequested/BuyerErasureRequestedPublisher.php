<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerErasureRequested;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerErasureRequested;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_erasure_requested')]
final readonly class BuyerErasureRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerErasureRequested::class)]
    public function __invoke(BuyerErasureRequested $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerErasureRequestedIntegrationEvent(
            buyerId: $event->id,
            requestedAt: $event->requestedAt,
        ));
    }
}
