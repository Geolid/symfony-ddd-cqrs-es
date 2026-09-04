<?php

declare(strict_types=1);

namespace Webhook\Consumer;

use Fulfilment\Shipping\Application\Command\DeliverShipment\DeliverShipment;
use Fulfilment\Shipping\Application\Query\GetShipmentByTrackingNumber\GetShipmentByTrackingNumber;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Webhook\Webhook\CarrierDeliveryParser;

#[AsRemoteEventConsumer(CarrierDeliveryParser::EVENT_TYPE)]
final readonly class CarrierDeliveryConsumer implements ConsumerInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function consume(RemoteEvent $event): void
    {
        $payload = $event->getPayload();

        $shipment = $this->queryBus->ask(new GetShipmentByTrackingNumber($payload['trackingNumber']));

        $this->commandBus->dispatch(new DeliverShipment($shipment->id));
    }
}
