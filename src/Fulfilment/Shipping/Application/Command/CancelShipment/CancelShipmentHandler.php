<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\CancelShipment;

use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ShipmentFinderInterface $finder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShipmentNotFoundException
     * @throws ShipmentAlreadyExistsException
     */
    public function __invoke(CancelShipment $command): void
    {
        $result = $this->finder->ofSourceOrNull($command->sourceId);

        if (null === $result) {
            return;
        }

        $shipment = $this->repository->load(ShipmentId::fromString($result->id));
        $shipment->cancel($this->clock->now());
        $this->repository->save($shipment);
    }
}
