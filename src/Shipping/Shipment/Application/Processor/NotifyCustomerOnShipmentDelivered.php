<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shipping\Shipment\Application\Notifier\ShipmentDeliveredNotifierInterface;
use Shipping\Shipment\Domain\Event\ShipmentDelivered;
use Shipping\Shipment\Domain\Exception\ShipmentNotFoundException;
use Shipping\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Shipping\Shipment\Domain\ShipmentId;

#[Processor('shipping.shipment.notify_on_delivered')]
final readonly class NotifyCustomerOnShipmentDelivered
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
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

        $this->notifier->notify($event->id, $shipment->orderId(), $shipment->customerId());
    }
}
