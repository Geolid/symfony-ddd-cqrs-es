<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\RequestShipment;

use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Mapper\PostalAddressMapper;

#[CommandHandler]
final readonly class RequestShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RequestShipment $command): void
    {
        $id = ShipmentId::fromString($command->id);
        $shipment = Shipment::request(
            id: $id,
            orderId: $command->orderId,
            buyerId: $command->buyerId,
            origin: PostalAddressMapper::fromArray($command->origin),
            destination: PostalAddressMapper::fromArray($command->destination),
            createdAt: $this->clock->now(),
        );

        try {
            $this->repository->save($shipment);
        } catch (ShipmentAlreadyExistsException) {
            return;
        }
    }
}
