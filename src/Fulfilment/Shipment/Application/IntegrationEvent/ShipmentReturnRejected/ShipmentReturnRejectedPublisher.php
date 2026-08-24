<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnRejected;

use Fulfilment\Shipment\Domain\Event\ShipmentReturnRejected;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipment.shipment_return_rejected_publisher')]
final readonly class ShipmentReturnRejectedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentReturnRejected::class)]
    public function __invoke(ShipmentReturnRejected $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentReturnRejectedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->repository->load(ShipmentId::fromString($event->id))->orderId,
            reason: $event->reason,
            rejectedAt: $event->rejectedAt,
        ));
    }
}
