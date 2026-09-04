<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerErased;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerErased;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_erased')]
final readonly class BuyerErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerErased::class)]
    public function __invoke(BuyerErased $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerErasedIntegrationEvent(
            buyerId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
