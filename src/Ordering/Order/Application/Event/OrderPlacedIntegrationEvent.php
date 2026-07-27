<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

/**
 * The one public contract Ordering exposes for "an order was placed". Other Bounded Contexts
 * (e.g. Shipping) subscribe to this instead of the Domain Event, which never leaves the BC
 * (ADR-001).
 */
#[Event('ordering.order.integration.placed')]
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
