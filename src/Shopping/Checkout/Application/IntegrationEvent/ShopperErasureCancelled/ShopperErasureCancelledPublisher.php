<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Checkout\Domain\Event\ShopperErasureCancelled;
use Shopping\Checkout\Domain\Shopper;

#[Publisher('shopping.checkout.publish_shopper_erasure_cancelled')]
final readonly class ShopperErasureCancelledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ShopperErasureCancelled::class)]
    public function __invoke(ShopperErasureCancelled $event): void
    {
        $this->publisher->publish(Shopper::class, $event->id, new ShopperErasureCancelledIntegrationEvent(
            shopperId: $event->id,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
