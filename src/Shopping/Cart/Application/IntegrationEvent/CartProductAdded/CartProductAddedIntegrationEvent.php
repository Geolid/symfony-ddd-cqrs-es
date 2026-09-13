<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartProductAdded;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.cart.cart.product_added')]
final readonly class CartProductAddedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $cartId,
        public string $productId,
        public int $quantity,
        public \DateTimeImmutable $addedAt,
    ) {
    }
}
