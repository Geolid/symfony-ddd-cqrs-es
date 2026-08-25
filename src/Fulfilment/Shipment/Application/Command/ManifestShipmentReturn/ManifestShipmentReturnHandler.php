<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\ManifestShipmentReturn;

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
use Shared\Application\Command\CommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[CommandHandler]
final readonly class ManifestShipmentReturnHandler
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
    public function __invoke(ManifestShipmentReturn $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));

        $shipment->manifestReturn(TrackingReference::fromString($command->returnTrackingReference), $this->clock->now());

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::RETURN_TRACKING_REFERENCE), $command->returnTrackingReference, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw TrackingReferenceAlreadyTakenException::forReference($command->returnTrackingReference, $e);
        }

        $this->repository->save($shipment);
    }
}
