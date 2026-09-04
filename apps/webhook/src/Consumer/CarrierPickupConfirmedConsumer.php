<?php

declare(strict_types=1);

namespace Webhook\Consumer;

use Fulfilment\Shipping\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipping\Application\Query\GetShipmentByTrackingNumber\GetShipmentByTrackingNumber;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Webhook\Webhook\CarrierPickupConfirmedParser;

#[AsRemoteEventConsumer(CarrierPickupConfirmedParser::EVENT_TYPE)]
final readonly class CarrierPickupConfirmedConsumer implements ConsumerInterface
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

        $this->commandBus->dispatch(new DispatchShipment($shipment->id));
    }
}
