<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperErased;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.checkout.shopper.erased')]
final readonly class ShopperErasedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shopperId,
        public \DateTimeImmutable $erasedAt,
    ) {
    }
}
