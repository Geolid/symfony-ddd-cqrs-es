<?php

declare(strict_types=1);

namespace Webhook\Consumer;

use Fulfilment\Shipment\Application\Command\DispatchShipmentReturn\DispatchShipmentReturn;
use Fulfilment\Shipment\Application\Query\GetShipmentByReturnTrackingReference\GetShipmentByReturnTrackingReference;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Webhook\Webhook\CarrierReturnPickedUpParser;

#[AsRemoteEventConsumer(CarrierReturnPickedUpParser::EVENT_TYPE)]
final readonly class CarrierReturnPickedUpConsumer implements ConsumerInterface
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

        $shipment = $this->queryBus->ask(new GetShipmentByReturnTrackingReference($payload['returnTrackingReference']));

        $this->commandBus->dispatch(new DispatchShipmentReturn($shipment->id));
    }
}
