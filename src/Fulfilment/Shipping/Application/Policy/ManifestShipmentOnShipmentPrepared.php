<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipping\Application\Exception\TrackingNumberAlreadyTakenException;
use Fulfilment\Shipping\Domain\Event\ShipmentPrepared;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('fulfilment.shipping.manifest_shipment_on_shipment_prepared')]
final readonly class ManifestShipmentOnShipmentPrepared
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private CarrierGatewayInterface $carrier,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws TrackingNumberAlreadyTakenException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(ShipmentPrepared::class)]
    public function __invoke(ShipmentPrepared $event): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($event->id));

        $trackingNumber = $this->carrier->manifest($event->id, $shipment->origin, $shipment->destination);

        $this->commandBus->dispatch(new ManifestShipment(
            id: $event->id,
            trackingNumber: $trackingNumber,
        ));
    }
}
