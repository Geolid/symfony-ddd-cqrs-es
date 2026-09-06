<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Manifest;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipping\Application\Finder\PaymentCapture\PaymentCaptureFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\PostalAddressResult;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Manifest\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Application\ShipmentDirection;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class ShipmentManifester implements ShipmentManifesterInterface
{
    public function __construct(
        private ShipmentFinderInterface $shipmentFinder,
        private PaymentCaptureFinderInterface $paymentCaptureFinder,
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

        if (ShipmentDirection::OUTBOUND === $shipment->direction) {
            $paymentCapture = $this->paymentCaptureFinder->ofOrderOrNull($shipment->sourceId);

            if (null === $paymentCapture || !$paymentCapture->captured) {
                throw ManifestDeniedException::forUncapturedPayment($shipmentId);
            }
        }

        $trackingNumber = $this->carrier->manifest($shipmentId, $this->toPostalAddress($shipment->origin), $this->toPostalAddress($shipment->destination));

        $this->commandBus->dispatch(new ManifestShipment(
            id: $shipmentId,
            trackingNumber: $trackingNumber,
        ));

        return $trackingNumber;
    }

    private function toPostalAddress(PostalAddressResult $address): PostalAddress
    {
        return PostalAddress::of(
            $address->recipientName,
            Address::of($address->address->street, $address->address->postalCode, $address->address->city, $address->address->countryCode),
        );
    }
}
