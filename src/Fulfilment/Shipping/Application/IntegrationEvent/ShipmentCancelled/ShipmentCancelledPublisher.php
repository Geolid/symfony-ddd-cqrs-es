<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentCancelled;

use Fulfilment\Shipping\Domain\Event\ShipmentCancelled;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipping.publish_shipment_cancelled')]
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
            sourceId: $this->repository->load(ShipmentId::fromString($event->id))->sourceId,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
