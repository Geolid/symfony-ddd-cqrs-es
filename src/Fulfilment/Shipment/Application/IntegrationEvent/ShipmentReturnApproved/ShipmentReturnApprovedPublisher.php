<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnApproved;

use Fulfilment\Shipment\Domain\Event\ShipmentReturnApproved;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipment.publish_shipment_return_approved')]
final readonly class ShipmentReturnApprovedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentReturnApproved::class)]
    public function __invoke(ShipmentReturnApproved $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentReturnApprovedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->repository->load(ShipmentId::fromString($event->id))->orderId,
            approvedAt: $event->approvedAt,
        ));
    }
}
