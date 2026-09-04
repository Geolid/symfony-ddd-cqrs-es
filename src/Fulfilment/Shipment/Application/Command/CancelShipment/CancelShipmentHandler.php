<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CancelShipment;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
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
        $result = $this->finder->ofReferenceOrNull($command->reference);

        if (null === $result) {
            return;
        }

        $shipment = $this->repository->load(ShipmentId::fromString($result->id));
        $shipment->cancel($this->clock->now());
        $this->repository->save($shipment);
    }
}
