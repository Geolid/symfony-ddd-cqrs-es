<?php

declare(strict_types=1);

namespace Shipping\Shipment\Infrastructure\Persistence\Projection\Reducer;

use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Message\Reducer;
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

        /** @var array{customerId: ?string, totalAmountInCents: ?int} $state */
        $state = (new Reducer())
            ->initState(['customerId' => null, 'totalAmountInCents' => null])
            ->when(
                OrderPlacedIntegrationEvent::class,
                static function (Message $message, array $state): array {
                    /** @var OrderPlacedIntegrationEvent $event */
                    $event = $message->event();

                    return ['customerId' => $event->customerId, 'totalAmountInCents' => $event->totalAmountInCents];
                },
            )
            ->reduce($stream);

        if (null === $state['customerId'] || null === $state['totalAmountInCents']) {
            return null;
        }

        return new OrderSummary($state['customerId'], $state['totalAmountInCents']);
    }
}
