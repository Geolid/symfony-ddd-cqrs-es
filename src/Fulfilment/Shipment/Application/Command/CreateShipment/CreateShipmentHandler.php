<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CreateShipment;

use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;

#[AsCommandHandler]
final readonly class CreateShipmentHandler
{
    public function __construct(
        private ShipmentRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CreateShipment $command): void
    {
        $id = ShipmentId::fromString($command->id);

        if ($this->repository->has($id)) {
            return;
        }

        $shipment = Shipment::create(
            id: $id,
            orderId: $command->orderId,
            customerId: $command->customerId,
            shippingAddress: PostalAddress::of(
                FullName::of($command->shippingFirstName, $command->shippingLastName),
                Address::of($command->shippingStreet, $command->shippingPostalCode, $command->shippingCity),
            ),
            createdAt: $this->clock->now(),
        );

        $this->repository->save($shipment);
    }
}
