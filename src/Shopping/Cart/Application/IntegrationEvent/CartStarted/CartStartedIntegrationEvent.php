<?php

declare(strict_types=1);

namespace Shopping\Cart\Application\IntegrationEvent\CartStarted;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.cart.cart.started')]
final readonly class CartStartedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $cartId,
        public string $customerId,
        public \DateTimeImmutable $startedAt,
    ) {
    }
}
