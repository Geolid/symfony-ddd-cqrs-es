<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\RequestShipment;

use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentDirection;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

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

        if ($this->repository->has($id)) {
            return;
        }

        $shipment = Shipment::request(
            id: $id,
            reference: $command->reference,
            direction: ShipmentDirection::from($command->direction->value),
            buyerId: $command->buyerId,
            origin: $this->toPostalAddress($command->origin),
            destination: $this->toPostalAddress($command->destination),
            createdAt: $this->clock->now(),
        );

        try {
            $this->repository->save($shipment);
        } catch (ShipmentAlreadyExistsException) {
            return;
        }
    }

    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $address
     */
    private function toPostalAddress(array $address): PostalAddress
    {
        return PostalAddress::of(
            $address['recipientName'],
            Address::of($address['street'], $address['postalCode'], $address['city'], $address['countryCode']),
        );
    }
}
