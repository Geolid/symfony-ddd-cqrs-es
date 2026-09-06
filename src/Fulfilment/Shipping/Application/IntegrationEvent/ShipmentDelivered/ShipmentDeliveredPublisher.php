<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDelivered;

use Fulfilment\Shipping\Domain\Event\ShipmentDelivered;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipping.publish_shipment_delivered')]
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
            sourceId: $this->repository->load(ShipmentId::fromString($event->id))->sourceId,
            deliveredAt: $event->deliveredAt,
        ));
    }
}
