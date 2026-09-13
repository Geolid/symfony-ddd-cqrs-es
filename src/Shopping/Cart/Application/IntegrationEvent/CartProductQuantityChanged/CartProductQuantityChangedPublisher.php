<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartProductQuantityChanged;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Cart\Domain\Cart;
use Shopping\Cart\Domain\Event\CartProductQuantityChanged;

#[Publisher('shopping.cart.publish_cart_product_quantity_changed')]
final readonly class CartProductQuantityChangedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CartProductQuantityChanged::class)]
    public function __invoke(CartProductQuantityChanged $event): void
    {
        $this->publisher->publish(Cart::class, $event->id->toString(), new CartProductQuantityChangedIntegrationEvent(
            cartId: $event->id->toString(),
            productId: $event->productId,
            quantity: $event->quantity->value,
            changedAt: $event->changedAt,
        ));
    }
}
