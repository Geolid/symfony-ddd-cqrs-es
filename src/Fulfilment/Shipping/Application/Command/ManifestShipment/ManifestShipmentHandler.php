<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\ManifestShipment;

use Fulfilment\Shipping\Application\Exception\TrackingNumberAlreadyTakenException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentUniqueKey;
use Fulfilment\Shipping\Domain\ValueObject\TrackingNumber;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class ManifestShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentAlreadyTrackedException
     * @throws ShipmentInvalidTransitionException
     * @throws TrackingNumberAlreadyTakenException
     * @throws ShipmentAlreadyExistsException
     */
    public function __invoke(ManifestShipment $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));

        $shipment->manifest(TrackingNumber::fromString($command->trackingNumber), $this->clock->now());

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_NUMBER), $command->trackingNumber, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw TrackingNumberAlreadyTakenException::forTrackingNumber($command->trackingNumber, $e);
        }

        $this->repository->save($shipment);
    }
}
