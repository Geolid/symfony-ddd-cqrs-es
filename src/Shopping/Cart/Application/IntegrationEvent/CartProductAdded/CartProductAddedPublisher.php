<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartProductAdded;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Event\CartProductAdded;

#[Publisher('shopping.cart.publish_cart_product_added')]
final readonly class CartProductAddedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CartProductAdded::class)]
    public function __invoke(CartProductAdded $event): void
    {
        $this->publisher->publish(Cart::class, $event->id->toString(), new CartProductAddedIntegrationEvent(
            cartId: $event->id->toString(),
            productId: $event->productId,
            quantity: $event->quantity->value,
            addedAt: $event->addedAt,
        ));
    }
}
