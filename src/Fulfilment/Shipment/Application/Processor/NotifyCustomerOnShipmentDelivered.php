<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Notifier\CustomerAddressResolverInterface;
use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;

#[Processor('fulfilment.shipment.notify_customer_on_shipment_delivered')]
final readonly class NotifyCustomerOnShipmentDelivered
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private CustomerAddressResolverInterface $addressResolver,
        private ShipmentDeliveredNotifierInterface $notifier,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDelivered::class)]
    public function __invoke(ShipmentDelivered $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));
        $customerId = $shipment->customerId();

        $address = $this->addressResolver->resolveFor($customerId);

        if (null === $address) {
            return;
        }

        $this->notifier->notify(new ShipmentDeliveredNotification(
            shipmentId: $event->id,
            orderId: $shipment->orderId(),
            customerId: $customerId,
            customerAddress: $address,
        ));
    }
}
