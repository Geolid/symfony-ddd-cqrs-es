<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\MarkShipmentDelivered;

use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class MarkShipmentDeliveredHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentInvalidTransitionException
     */
    public function __invoke(MarkShipmentDelivered $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->markDelivered($this->clock->now());
        $this->repository->save($shipment);
    }
}
