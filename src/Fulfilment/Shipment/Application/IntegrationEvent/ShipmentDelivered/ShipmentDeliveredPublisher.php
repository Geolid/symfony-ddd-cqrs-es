<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDelivered;

use Fulfilment\Shipment\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipment.publish_shipment_delivered')]
final readonly class ShipmentDeliveredPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDelivered::class)]
    public function __invoke(ShipmentDelivered $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentDeliveredIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->repository->load(ShipmentId::fromString($event->id))->orderId,
            deliveredAt: $event->deliveredAt,
        ));
    }
}
