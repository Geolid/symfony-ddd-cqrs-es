<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Command\AssignTrackingReference\AssignTrackingReference;
use Fulfilment\Shipment\Domain\Event\ShipmentDispatched;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('fulfilment.shipment.request_pickup_on_shipment_dispatched')]
final readonly class RequestPickupOnShipmentDispatched
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private CarrierGatewayInterface $carrier,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentDispatched::class)]
    public function __invoke(ShipmentDispatched $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $this->commandBus->dispatch(new AssignTrackingReference(
            id: $event->id,
            trackingReference: $this->carrier->requestPickup($event->id, $shipment->customerAddress()),
        ));
    }
}
