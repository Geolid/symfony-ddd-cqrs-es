<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CancelShipment;

use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\AggregateNotFoundException;

#[AsCommandHandler]
final readonly class CancelShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AggregateNotFoundException
     */
    public function __invoke(CancelShipment $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->cancel($this->clock->now());
        $this->repository->save($shipment);
    }
}
