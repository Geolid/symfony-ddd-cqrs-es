<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Persistence\EventStore\Publisher;

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
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\EventStore\Publisher\IntegrationEventAppenderInterface;
use Shared\Infrastructure\Persistence\EventStore\Publisher\Publisher;

#[Publisher('fulfilment.shipment.integration')]
final readonly class ShipmentPublisher
{
    public function __construct(
        private IntegrationEventAppenderInterface $appender,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDispatched::class)]
    public function onShipmentDispatched(ShipmentDispatched $event): void
    {
        $this->appender->append(Shipment::class, $event->id, new ShipmentDispatchedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->orderIdFor($event->id),
            dispatchedAt: $event->dispatchedAt,
        ));
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDelivered::class)]
    public function onShipmentDelivered(ShipmentDelivered $event): void
    {
        $this->appender->append(Shipment::class, $event->id, new ShipmentDeliveredIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->orderIdFor($event->id),
            deliveredAt: $event->deliveredAt,
        ));
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentCancelled::class)]
    public function onShipmentCancelled(ShipmentCancelled $event): void
    {
        $this->appender->append(Shipment::class, $event->id, new ShipmentCancelledIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->orderIdFor($event->id),
            cancelledAt: $event->cancelledAt,
        ));
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentManifested::class)]
    public function onShipmentManifested(ShipmentManifested $event): void
    {
        $this->appender->append(Shipment::class, $event->id, new ShipmentManifestedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->orderIdFor($event->id),
            trackingReference: $event->trackingReference,
        ));
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentReturnApproved::class)]
    public function onShipmentReturnApproved(ShipmentReturnApproved $event): void
    {
        $this->appender->append(Shipment::class, $event->id, new ShipmentReturnApprovedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->orderIdFor($event->id),
            approvedAt: $event->approvedAt,
        ));
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentReturnRejected::class)]
    public function onShipmentReturnRejected(ShipmentReturnRejected $event): void
    {
        $this->appender->append(Shipment::class, $event->id, new ShipmentReturnRejectedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->orderIdFor($event->id),
            reason: $event->reason,
            rejectedAt: $event->rejectedAt,
        ));
    }

    /**
     * @throws ShipmentNotFoundException
     */
    private function orderIdFor(string $shipmentId): string
    {
        return $this->repository->load(ShipmentId::fromString($shipmentId))->orderId;
    }
}
