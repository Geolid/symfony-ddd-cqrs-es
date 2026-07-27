<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Persistence\Projection\Reducer;

use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\StreamCriterion;
use Patchlevel\EventSourcing\Store\Store;

/**
 * Reads Ordering's public Integration Event stream directly (never its aggregate/Domain layer)
 * to enrich Shipping's own projection with a denormalized snapshot at fold time — the
 * alternative to a live cross-BC join, and a different reaction shape than a Processor: this
 * one is read-side enrichment, not a side effect (see DbalShipmentProjector::onShipmentCreated,
 * infrastructure.md).
 */
final readonly class OrderSummaryReducer
{
    public function __construct(private Store $store)
    {
    }

    public function forOrder(string $orderId): ?OrderSummary
    {
        $stream = $this->store->load(new Criteria(
            new StreamCriterion(\sprintf('ordering.order.integration.%s', $orderId)),
        ));

        foreach ($stream as $message) {
            $event = $message->event();

            if ($event instanceof OrderPlacedIntegrationEvent) {
                return new OrderSummary($event->customerId, $event->totalAmountInCents);
            }
        }

        return null;
    }
}
