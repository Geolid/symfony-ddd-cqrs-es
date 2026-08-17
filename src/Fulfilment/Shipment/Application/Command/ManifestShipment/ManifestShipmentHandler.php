<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\ManifestShipment;

use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentUniqueKey;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[AsCommandHandler]
final readonly class ManifestShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     * @throws ShipmentNotFoundException
     * @throws TrackingReferenceAlreadyTakenException
     * @throws ShipmentAlreadyExistsException
     */
    public function __invoke(ManifestShipment $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));

        $shipment->manifest(TrackingReference::fromString($command->trackingReference), $this->clock->now());

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_REFERENCE), $command->trackingReference, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw TrackingReferenceAlreadyTakenException::forReference($command->trackingReference, $e);
        }

        $this->repository->save($shipment);
    }
}
