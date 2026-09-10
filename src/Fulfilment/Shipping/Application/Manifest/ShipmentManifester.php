<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Manifest;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipping\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Manifest\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Mapper\PostalAddressMapper;

final readonly class ShipmentManifester implements ShipmentManifesterInterface
{
    public function __construct(
        private ShipmentFinderInterface $shipmentFinder,
        private OrderPaymentFinderInterface $orderPaymentFinder,
        private CarrierGatewayInterface $carrier,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ShipmentResultNotFoundException
     * @throws ManifestDeniedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function manifest(string $shipmentId): string
    {
        $shipment = $this->shipmentFinder->ofId($shipmentId);

        if (ShipmentStatus::CANCELLED === $shipment->status) {
            throw ManifestDeniedException::forCancelledShipment($shipmentId);
        }

        $orderPayment = $this->orderPaymentFinder->ofOrderOrNull($shipment->orderId);

        if (null === $orderPayment || !$orderPayment->paid) {
            throw ManifestDeniedException::forUnpaidOrder($shipmentId);
        }

        $trackingNumber = $this->carrier->manifest(
            $shipmentId,
            PostalAddressMapper::fromArray(['recipientName' => $shipment->origin->recipientName, 'address' => (array) $shipment->origin->address]),
            PostalAddressMapper::fromArray(['recipientName' => $shipment->destination->recipientName, 'address' => (array) $shipment->destination->address]),
        );

        $this->commandBus->dispatch(new ManifestShipment(
            id: $shipmentId,
            trackingNumber: $trackingNumber,
        ));

        return $trackingNumber;
    }
}
