<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Carrier\Acme;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierFatalFailureException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierGatewayException;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class AcmeCarrierGateway implements CarrierGatewayInterface
{
    private const string SHIPMENT_PATH = '/shipments';
    private const string TRACKER_PATH = '/trackers';

    public function __construct(private AcmeClient $acmeClient)
    {
    }

    /**
     * @throws CarrierGatewayException
     */
    public function manifest(string $shipmentId, PostalAddress $origin, PostalAddress $destination): string
    {
        $response = $this->acmeClient->post(self::SHIPMENT_PATH, [
            'merchantReference' => $shipmentId,
            'origin' => $this->postalAddressPayload($origin),
            'destination' => $this->postalAddressPayload($destination),
        ], $shipmentId);

        $trackingNumber = $response['trackingNumber'] ?? null;

        if (!\is_string($trackingNumber) || '' === $trackingNumber) {
            throw CarrierFatalFailureException::forReason('A manifest response carries a non-empty "trackingNumber".');
        }

        return $trackingNumber;
    }

    /**
     * @throws CarrierGatewayException
     */
    public function checkStatus(string $reference): CarrierGatewayStatus
    {
        $response = $this->acmeClient->get(self::TRACKER_PATH.'/'.$reference);

        $status = $response['status'] ?? null;

        if (!\is_string($status) || '' === $status) {
            throw CarrierFatalFailureException::forReason('A status response carries a non-empty "status".');
        }

        try {
            return CarrierGatewayStatus::from($status);
        } catch (\ValueError) {
            throw CarrierFatalFailureException::forReason(\sprintf('A status response carries a recognized "status", got "%s".', $status));
        }
    }

    /**
     * @return array{recipient: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function postalAddressPayload(PostalAddress $postalAddress): array
    {
        return [
            'recipient' => $postalAddress->recipientName,
            'street' => $postalAddress->address->street,
            'postalCode' => $postalAddress->address->postalCode,
            'city' => $postalAddress->address->city,
            'countryCode' => $postalAddress->address->countryCode->value,
        ];
    }
}
