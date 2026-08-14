<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\EventStore\Translator;

use Fulfilment\Shipment\Application\Event\ShipmentCancelledIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentTrackingReferenceAssignedIntegrationEvent;
use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\TrackingReferenceAssigned;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Store\Store;
use Shared\Domain\Exception\AggregateNotFoundException;
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
     * @throws AggregateNotFoundException
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
     * @throws AggregateNotFoundException
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
     * @throws AggregateNotFoundException
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
     * @throws AggregateNotFoundException
     */
    #[Subscribe(TrackingReferenceAssigned::class)]
    public function onTrackingReferenceAssigned(TrackingReferenceAssigned $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            IntegrationStreamId::build('fulfilment.shipment', $event->id),
            new ShipmentTrackingReferenceAssignedIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                trackingReference: $event->trackingReference,
            ),
        );
    }
}
