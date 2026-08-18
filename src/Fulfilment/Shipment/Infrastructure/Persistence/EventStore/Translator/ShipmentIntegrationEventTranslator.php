<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\EventStore\Translator;

use Fulfilment\Shipment\Application\Event\ShipmentCancelledIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentManifestedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentReturnApprovedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentReturnRejectedIntegrationEvent;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\ShipmentManifested;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnApproved;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRejected;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Store\Store;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('fulfilment.shipment.integration')]
final readonly class ShipmentIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    public function __construct(
        Store $store,
        private ShipmentRepositoryInterface $repository,
    ) {
        parent::__construct($store);
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDispatched::class)]
    public function onShipmentDispatched(ShipmentDispatched $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentDispatchedIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                dispatchedAt: $event->dispatchedAt,
            ),
        );
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDelivered::class)]
    public function onShipmentDelivered(ShipmentDelivered $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentDeliveredIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                deliveredAt: $event->deliveredAt,
            ),
        );
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentCancelled::class)]
    public function onShipmentCancelled(ShipmentCancelled $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentCancelledIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                cancelledAt: $event->cancelledAt,
            ),
        );
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentManifested::class)]
    public function onShipmentManifested(ShipmentManifested $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentManifestedIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                trackingReference: $event->trackingReference,
            ),
        );
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentReturnApproved::class)]
    public function onShipmentReturnApproved(ShipmentReturnApproved $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentReturnApprovedIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                approvedAt: $event->approvedAt,
            ),
        );
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentReturnRejected::class)]
    public function onShipmentReturnRejected(ShipmentReturnRejected $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentReturnRejectedIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                reason: $event->reason,
                rejectedAt: $event->rejectedAt,
            ),
        );
    }
}
