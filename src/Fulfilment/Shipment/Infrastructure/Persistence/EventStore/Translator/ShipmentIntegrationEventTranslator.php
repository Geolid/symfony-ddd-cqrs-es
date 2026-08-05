<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\EventStore\Translator;

use Fulfilment\Shipment\Application\Event\ShipmentDeliveredIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentDispatchedIntegrationEvent;
use Fulfilment\Shipment\Application\Event\ShipmentTrackingReferenceAssignedIntegrationEvent;
use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Event\TrackingReferenceAssigned;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Store\Store;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('fulfilment.shipment.integration_translator')]
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
            \sprintf('fulfilment.shipment.integration.%s', $event->id),
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
            \sprintf('fulfilment.shipment.integration.%s', $event->id),
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
    #[Subscribe(TrackingReferenceAssigned::class)]
    public function onTrackingReferenceAssigned(TrackingReferenceAssigned $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->append(
            \sprintf('fulfilment.shipment.integration.%s', $event->id),
            new ShipmentTrackingReferenceAssignedIntegrationEvent(
                shipmentId: $event->id,
                orderId: $shipment->orderId(),
                trackingReference: $event->trackingReference,
            ),
        );
    }
}
