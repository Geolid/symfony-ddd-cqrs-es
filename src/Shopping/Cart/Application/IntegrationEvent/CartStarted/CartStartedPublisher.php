<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartStarted;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Event\CartStarted;

#[Publisher('shopping.cart.publish_cart_started')]
final readonly class CartStartedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CartStarted::class)]
    public function __invoke(CartStarted $event): void
    {
        $this->publisher->publish(Cart::class, $event->id->toString(), new CartStartedIntegrationEvent(
            cartId: $event->id->toString(),
            customerId: $event->customerId,
            startedAt: $event->startedAt,
        ));
    }
}
