<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentManifested;

use Fulfilment\Shipment\Domain\Event\ShipmentManifested;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipment.publish_shipment_manifested')]
final readonly class ShipmentManifestedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentManifested::class)]
    public function __invoke(ShipmentManifested $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentManifestedIntegrationEvent(
            shipmentId: $event->id,
            reference: $this->repository->load(ShipmentId::fromString($event->id))->reference,
            trackingNumber: $event->trackingNumber,
        ));
    }
}
