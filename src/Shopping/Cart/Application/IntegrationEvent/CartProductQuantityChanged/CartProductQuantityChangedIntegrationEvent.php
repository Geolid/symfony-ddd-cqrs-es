<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartProductQuantityChanged;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.cart.cart.product_quantity_changed')]
final readonly class CartProductQuantityChangedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $cartId,
        public string $productId,
        public int $quantity,
        public \DateTimeImmutable $changedAt,
    ) {
    }
}
