<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\ApproveShipmentErasure;

use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ApproveShipmentErasureHandler
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
    public function __invoke(ApproveShipmentErasure $command): void
    {
        $shipment = $this->repository->load(ShipmentId::fromString($command->id));
        $shipment->approveErasure($this->clock->now());
        $this->repository->save($shipment);
    }
}
