<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Notifier\ShipmentCancellationRejectedNotification;
use Fulfilment\Shipment\Application\Notifier\ShipmentCancellationRejectedNotifierInterface;
use Fulfilment\Shipment\Domain\Event\ShipmentCancellationRejected;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\Processor;

#[Processor('fulfilment.shipment.notify_customer_on_shipment_cancellation_rejected')]
final readonly class NotifyCustomerOnShipmentCancellationRejected
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ShipmentCancellationRejectedNotifierInterface $notifier,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentCancellationRejected::class)]
    public function __invoke(ShipmentCancellationRejected $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->notifier->notify(new ShipmentCancellationRejectedNotification(
            shipmentId: $event->id,
            orderId: $shipment->orderId(),
            customerId: $shipment->customerId(),
            customerAddress: $shipment->customerAddress(),
        ));
    }
}
