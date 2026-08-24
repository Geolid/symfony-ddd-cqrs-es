<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentCancelled;

use Fulfilment\Shipment\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipment.shipment_cancelled_publisher')]
final readonly class ShipmentCancelledPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentCancelled::class)]
    public function __invoke(ShipmentCancelled $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentCancelledIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->repository->load(ShipmentId::fromString($event->id))->orderId,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
