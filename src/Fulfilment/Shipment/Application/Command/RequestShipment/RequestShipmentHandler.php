<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\RequestShipment;

use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandUseCase;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandUseCase]
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

        if ($this->repository->has($id)) {
            return;
        }

        $shipment = Shipment::request(
            id: $id,
            orderId: $command->orderId,
            customerId: $command->customerId,
            shippingAddress: PostalAddress::of(
                FullName::of($command->shippingAddress['firstName'], $command->shippingAddress['lastName']),
                Address::of($command->shippingAddress['street'], $command->shippingAddress['postalCode'], $command->shippingAddress['city']),
            ),
            createdAt: $this->clock->now(),
        );

        try {
            $this->repository->save($shipment);
        } catch (ShipmentAlreadyExistsException) {
            return;
        }
    }
}
