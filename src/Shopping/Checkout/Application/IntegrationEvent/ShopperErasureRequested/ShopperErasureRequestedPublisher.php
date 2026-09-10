<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperErasureRequested;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Checkout\Domain\Event\ShopperErasureRequested;
use Shopping\Checkout\Domain\Shopper;

#[Publisher('shopping.checkout.publish_shopper_erasure_requested')]
final readonly class ShopperErasureRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ShopperErasureRequested::class)]
    public function __invoke(ShopperErasureRequested $event): void
    {
        $this->publisher->publish(Shopper::class, $event->id, new ShopperErasureRequestedIntegrationEvent(
            shopperId: $event->id,
            requestedAt: $event->requestedAt,
        ));
    }
}
