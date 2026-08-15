<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentUniqueValue;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Processor\Processor;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[Processor('fulfilment.shipment.manifest_shipment_on_shipment_prepared')]
final readonly class ManifestShipmentOnShipmentPrepared
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private CarrierGatewayInterface $carrier,
        private UniqueValueRegistryInterface $uniqueValues,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws TrackingReferenceAlreadyTakenException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentPrepared::class)]
    public function __invoke(ShipmentPrepared $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $trackingReference = $this->carrier->requestPickup($event->id, $shipment->shippingAddress());

        try {
            $this->uniqueValues->reserve(ShipmentUniqueValue::TRACKING_REFERENCE, $trackingReference);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw TrackingReferenceAlreadyTakenException::forReference($trackingReference, $e);
        }

        $this->commandBus->dispatch(new ManifestShipment(
            id: $event->id,
            trackingReference: $trackingReference,
        ));
    }
}
