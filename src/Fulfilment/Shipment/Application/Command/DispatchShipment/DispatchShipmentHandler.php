<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\DispatchShipment;

use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class DispatchShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentInvalidTransitionException
     * @throws ShipmentAlreadyExistsException
     */
    public function __invoke(DispatchShipment $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->dispatch($this->clock->now());
        $this->repository->save($shipment);
    }
}
