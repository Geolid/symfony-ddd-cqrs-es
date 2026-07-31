<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Mailing\ShipmentDeliveredNotification;
use Fulfilment\Shipment\Application\Mailing\ShipmentMailerInterface;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;

#[Processor('fulfilment.shipment.notify_on_delivered')]
final readonly class NotifyCustomerOnShipmentDelivered
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ShipmentMailerInterface $mailer,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDelivered::class)]
    public function onShipmentDelivered(ShipmentDelivered $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->mailer->sendDelivered(new ShipmentDeliveredNotification(
            shipmentId: $event->id,
            orderId: $shipment->orderId(),
            customerId: $shipment->customerId(),
        ));
    }
}
