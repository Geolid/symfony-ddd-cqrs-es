<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerErasureCancelled;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_erasure_cancelled')]
final readonly class BuyerErasureCancelledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerErasureCancelled::class)]
    public function __invoke(BuyerErasureCancelled $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerErasureCancelledIntegrationEvent(
            buyerId: $event->id,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
