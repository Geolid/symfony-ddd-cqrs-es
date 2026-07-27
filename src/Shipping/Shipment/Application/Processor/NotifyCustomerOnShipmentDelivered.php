<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shipping\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Shipping\Shipment\Domain\ShipmentId;
use Shipping\Shipment\Infrastructure\Persistence\Projection\Reducer\OrderSummaryReducer;

/**
 * The third reaction shape, alongside CreateShipmentOnOrderPlaced (a cross-BC Processor
 * dispatching a Command) and DbalShipmentProjector (a Reducer/fan-out enriching a read model):
 * an intra-BC Processor reacting to Shipping's own Domain Event and calling an outbound port
 * directly, no Command involved. Enrichment (the customer ID) goes through the Repository and
 * the same Reducer the projection uses — a Finder's projection could lag behind the event
 * being handled right now, the event store never does.
 */
#[Processor('shipping.shipment.notify_on_delivered')]
final readonly class NotifyCustomerOnShipmentDelivered
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private OrderSummaryReducer $orderSummary,
        private ShipmentDeliveredNotifierInterface $notifier,
    ) {
    }

    #[Subscribe(ShipmentDelivered::class)]
    public function __invoke(ShipmentDelivered $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));
        $order = $this->orderSummary->forOrder($shipment->orderId());

        if (null === $order) {
            return;
        }

        $this->notifier->notify($event->id, $shipment->orderId(), $order->customerId);
    }
}
