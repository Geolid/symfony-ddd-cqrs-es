<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartProductRemoved;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.cart.cart.product_removed')]
final readonly class CartProductRemovedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $cartId,
        public string $productId,
        public \DateTimeImmutable $removedAt,
    ) {
    }
}
