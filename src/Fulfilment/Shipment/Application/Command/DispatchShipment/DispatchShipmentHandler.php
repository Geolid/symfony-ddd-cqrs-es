<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\DispatchShipment;

use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\AggregateNotFoundException;

#[AsCommandHandler]
final readonly class DispatchShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AggregateNotFoundException
     * @throws ShipmentInvalidTransitionException
     */
    public function __invoke(DispatchShipment $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->dispatch($this->clock->now());
        $this->repository->save($shipment);
    }
}
