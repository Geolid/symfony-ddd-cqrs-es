<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDispatched;

use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipment.shipment_dispatched_publisher')]
final readonly class ShipmentDispatchedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentDispatched::class)]
    public function __invoke(ShipmentDispatched $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentDispatchedIntegrationEvent(
            shipmentId: $event->id,
            orderId: $this->repository->load(ShipmentId::fromString($event->id))->orderId,
            dispatchedAt: $event->dispatchedAt,
        ));
    }
}
