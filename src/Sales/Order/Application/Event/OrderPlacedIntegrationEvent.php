<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

/**
 * The one public contract Sales exposes for "an order was placed". Other Bounded Contexts
 * (e.g. Fulfilment) subscribe to this instead of the Domain Event, which never leaves the BC.
 */
#[Event('sales.order.integration.placed')]
final readonly class OrderPlacedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        public int $totalAmountInCents,
        public string $placedAt,
    ) {
    }
}
