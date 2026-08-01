<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\AssignTrackingReference;

use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class AssignTrackingReferenceHandler
{
    public function __construct(private ShipmentRepositoryInterface $repository)
    {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentInvalidTransitionException
     */
    public function __invoke(AssignTrackingReference $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->assignTrackingReference($command->trackingReference);
        $this->repository->save($shipment);
    }
}
