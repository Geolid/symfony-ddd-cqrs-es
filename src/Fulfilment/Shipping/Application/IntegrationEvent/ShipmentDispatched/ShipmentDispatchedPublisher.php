<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentDispatched;

use Fulfilment\Shipping\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipping.publish_shipment_dispatched')]
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
        $this->publisher->publish(Shipment::class, $event->id->toString(), new ShipmentDispatchedIntegrationEvent(
            shipmentId: $event->id->toString(),
            orderId: $this->repository->load($event->id)->orderId,
            dispatchedAt: $event->dispatchedAt,
        ));
    }
}
