<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperErased;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Checkout\Domain\Event\ShopperErased;
use Shopping\Checkout\Domain\Shopper;

#[Publisher('shopping.checkout.publish_shopper_erased')]
final readonly class ShopperErasedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ShopperErased::class)]
    public function __invoke(ShopperErased $event): void
    {
        $this->publisher->publish(Shopper::class, $event->id, new ShopperErasedIntegrationEvent(
            shopperId: $event->id,
            erasedAt: $event->erasedAt,
        ));
    }
}
