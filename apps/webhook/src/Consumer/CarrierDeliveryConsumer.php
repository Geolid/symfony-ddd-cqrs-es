<?php

declare(strict_types=1);

namespace Webhook\Consumer;

use Fulfilment\Shipment\Application\Command\MarkShipmentDelivered\MarkShipmentDelivered;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Webhook\Webhook\CarrierDeliveryParser;

#[AsRemoteEventConsumer(CarrierDeliveryParser::EVENT_TYPE)]
final readonly class CarrierDeliveryConsumer implements ConsumerInterface
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function consume(RemoteEvent $event): void
    {
        $payload = $event->getPayload();
        \assert(\is_string($payload['shipmentId']));

        $this->commandBus->dispatch(new MarkShipmentDelivered($payload['shipmentId']));
    }
}
