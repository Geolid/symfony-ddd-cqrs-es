<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\IntegrationEvent\ShipmentPrepared;

use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
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
    public function __invoke(ShipmentPrepared $event, Message $message): void
    {
        $this->publisher->publish(new ShipmentPreparedIntegrationEvent(
            shipmentId: $event->id->toString(),
            orderId: $this->repository->load($event->id)->orderId,
            preparedAt: $event->preparedAt,
        ), $message);
    }
}
