<?php

declare(strict_types=1);

namespace Sales\Order\Application\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\Event\IntegrationEventInterface;

/**
 * The public contract Sales exposes for "an order was cancelled" — published after the
 * order (and, typically, its Shipment) already exist, unlike OrderPlacedIntegrationEvent which
 * a consumer only ever sees replayed from history (see
 * Fulfilment\Shipment\Infrastructure\Persistence\Projection\Reducer\OrderSummaryReducer). A live
 * consumer reacts to this one directly via #[Subscribe] instead (see
 * Fulfilment\Shipment\Infrastructure\Persistence\Projection\Projector\DbalShipmentProjector).
 */
#[Event('sales.order.integration.cancelled')]
final readonly class OrderCancelledIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $orderId,
        public string $cancelledAt,
    ) {
    }
}
