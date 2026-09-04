<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\RequestShipment;

use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyExistsException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
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
            customerId: $command->customerId,
            origin: $this->toAddress($command->origin),
            destination: $this->toAddress($command->destination),
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
    private function toAddress(array $address): PostalAddress
    {
        return PostalAddress::of(
            $address['recipientName'],
            Address::of($address['street'], $address['postalCode'], $address['city'], $address['countryCode']),
        );
    }
}
