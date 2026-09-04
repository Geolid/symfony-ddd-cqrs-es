<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\IntegrationEvent\BuyerRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.buyer.publish_buyer_registered')]
final readonly class BuyerRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(BuyerRegistered::class)]
    public function __invoke(BuyerRegistered $event): void
    {
        $this->publisher->publish(Buyer::class, $event->id, new BuyerRegisteredIntegrationEvent(
            buyerId: $event->id,
            email: $event->email,
            registeredAt: $event->registeredAt,
        ));
    }
}
