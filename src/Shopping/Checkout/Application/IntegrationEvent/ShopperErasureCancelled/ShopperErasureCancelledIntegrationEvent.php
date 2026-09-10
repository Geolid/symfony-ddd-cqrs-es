<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\ShopperErasureCancelled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.checkout.shopper.erasure_cancelled')]
final readonly class ShopperErasureCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $shopperId,
        public \DateTimeImmutable $cancelledAt,
    ) {
    }
}
