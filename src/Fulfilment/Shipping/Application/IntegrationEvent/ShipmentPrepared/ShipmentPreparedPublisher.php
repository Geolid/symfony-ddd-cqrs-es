<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared;

use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('fulfilment.shipping.publish_shipment_prepared')]
final readonly class ShipmentPreparedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private ShipmentRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     */
    #[Subscribe(ShipmentPrepared::class)]
    public function __invoke(ShipmentPrepared $event): void
    {
        $this->publisher->publish(Shipment::class, $event->id, new ShipmentPreparedIntegrationEvent(
            shipmentId: $event->id,
            sourceId: $this->repository->load(ShipmentId::fromString($event->id))->sourceId,
            preparedAt: $event->preparedAt,
        ));
    }
}
