<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\RequestShipmentReturn;

use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandUseCase;

#[CommandUseCase]
final readonly class RequestShipmentReturnHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentAlreadyExistsException
     */
    public function __invoke(RequestShipmentReturn $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->requestReturn($this->clock->now());
        $this->repository->save($shipment);
    }
}
