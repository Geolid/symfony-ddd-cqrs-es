<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Command\ManifestShipmentReturn\ManifestShipmentReturn;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Domain\Event\ShipmentReturnRequested;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;

#[Processor('fulfilment.shipment.manifest_shipment_return_on_shipment_return_requested')]
final readonly class ManifestShipmentReturnOnShipmentReturnRequested
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private CarrierGatewayInterface $carrier,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws TrackingReferenceAlreadyTakenException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentReturnRequested::class)]
    public function __invoke(ShipmentReturnRequested $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $returnTrackingReference = $this->carrier->requestReturnPickup($event->id, $shipment->shippingAddress);

        $this->commandBus->dispatch(new ManifestShipmentReturn(
            id: $event->id,
            returnTrackingReference: $returnTrackingReference,
        ));
    }
}
