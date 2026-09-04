<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Infrastructure\Carrier\Acme;

use Fulfilment\Shipping\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipping\Application\Carrier\CarrierGatewayStatus;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class AcmeCarrierGateway implements CarrierGatewayInterface
{
    private const string SHIPMENT_PATH = '/shipments';
    private const string TRACKER_PATH = '/trackers';

    public function __construct(private AcmeClient $acmeClient)
    {
    }

    /**
     * @throws AcmeClientException
     */
    public function manifest(string $shipmentId, PostalAddress $origin, PostalAddress $destination): string
    {
        $response = $this->acmeClient->post(self::SHIPMENT_PATH, [
            'clientReferenceId' => $shipmentId,
            'origin' => $this->addressPayload($origin),
            'destination' => $this->addressPayload($destination),
        ], $shipmentId);

        $trackingNumber = $response['trackingNumber'] ?? null;

        if (!\is_string($trackingNumber) || '' === $trackingNumber) {
            throw AcmeClientException::invalidResponse(self::SHIPMENT_PATH, 'A manifest response carries a non-empty "trackingNumber".');
        }

        return $trackingNumber;
    }

    /**
     * @throws AcmeClientException
     */
    public function checkStatus(string $reference): CarrierGatewayStatus
    {
        $response = $this->acmeClient->get(self::TRACKER_PATH.'/'.$reference);

        $status = $response['status'] ?? null;

        if (!\is_string($status) || '' === $status) {
            throw AcmeClientException::invalidResponse(self::TRACKER_PATH, 'A status response carries a non-empty "status".');
        }

        try {
            return CarrierGatewayStatus::from($status);
        } catch (\ValueError) {
            throw AcmeClientException::invalidResponse(self::TRACKER_PATH, \sprintf('A status response carries a recognized "status", got "%s".', $status));
        }
    }

    /**
     * @return array{recipient: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function addressPayload(PostalAddress $address): array
    {
        return [
            'recipient' => $address->recipientName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
            'countryCode' => $address->address->countryCode->value,
        ];
    }
}
