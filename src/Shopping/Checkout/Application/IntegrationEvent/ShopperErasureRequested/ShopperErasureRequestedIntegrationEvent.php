<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperErasureRequested;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.checkout.shopper.erasure_requested')]
final readonly class ShopperErasureRequestedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shopperId,
        public \DateTimeImmutable $requestedAt,
    ) {
    }
}
