<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionStaled;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.shopping.checkout.checkout_session.staled')]
final readonly class CheckoutSessionStaledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $checkoutSessionId,
        public \DateTimeImmutable $staledAt,
    ) {
    }
}
