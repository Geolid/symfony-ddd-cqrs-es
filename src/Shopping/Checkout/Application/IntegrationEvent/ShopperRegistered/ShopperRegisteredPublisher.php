<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperRegistered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Checkout\Domain\Event\ShopperRegistered;
use Shopping\Checkout\Domain\Shopper;

#[Publisher('shopping.checkout.publish_shopper_registered')]
final readonly class ShopperRegisteredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(ShopperRegistered::class)]
    public function __invoke(ShopperRegistered $event): void
    {
        $this->publisher->publish(Shopper::class, $event->id, new ShopperRegisteredIntegrationEvent(
            shopperId: $event->id,
            identityId: $event->identityId,
            email: $event->email->value,
            registeredAt: $event->registeredAt,
        ));
    }
}
