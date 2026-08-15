<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\AssignTrackingReference;

use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentUniqueValue;
use Fulfilment\Shipment\Domain\ValueObject\TrackingReference;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class AssignTrackingReferenceHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentInvalidTransitionException
     * @throws TrackingReferenceAlreadyTakenException
     */
    public function __invoke(AssignTrackingReference $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));

        try {
            $this->uniqueValues->reserve(ShipmentUniqueValue::TRACKING_REFERENCE, $command->trackingReference);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw TrackingReferenceAlreadyTakenException::forReference($command->trackingReference, $e);
        }

        $shipment->assignTrackingReference(TrackingReference::fromString($command->trackingReference));
        $this->repository->save($shipment);
    }
}
