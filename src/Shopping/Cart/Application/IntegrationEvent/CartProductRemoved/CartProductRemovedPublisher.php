<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartProductRemoved;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Event\CartProductRemoved;

#[Publisher('shopping.cart.publish_cart_product_removed')]
final readonly class CartProductRemovedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CartProductRemoved::class)]
    public function __invoke(CartProductRemoved $event, Message $message): void
    {
        $this->publisher->publish(new CartProductRemovedIntegrationEvent(
            cartId: $event->id->toString(),
            productId: $event->productId,
            removedAt: $event->removedAt,
        ), $message);
    }
}
