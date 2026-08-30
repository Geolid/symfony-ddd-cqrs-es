<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Carrier\Acme;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Application\Carrier\CarrierGatewayStatus;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AcmeCarrierGateway implements CarrierGatewayInterface
{
    private const string SHIPMENT_PATH = '/shipments';
    private const string RETURN_PATH = '/returns';
    private const string TRACKER_PATH = '/trackers';

    public function __construct(
        private AcmeClient $acmeClient,
        #[Autowire(param: 'fulfilment.return_address_recipient')]
        private string $returnAddressRecipient,
        #[Autowire(param: 'fulfilment.return_address_street')]
        private string $returnAddressStreet,
        #[Autowire(param: 'fulfilment.return_address_postal_code')]
        private string $returnAddressPostalCode,
        #[Autowire(param: 'fulfilment.return_address_city')]
        private string $returnAddressCity,
        #[Autowire(param: 'fulfilment.return_address_country_code')]
        private string $returnAddressCountryCode,
    ) {
    }

    /**
     * @throws AcmeClientException
     */
    public function manifest(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        $response = $this->acmeClient->post(self::SHIPMENT_PATH, [
            'clientReferenceId' => $shipmentId,
            'destination' => [
                'recipient' => $deliveryAddress->fullName->toString(),
                'street' => $deliveryAddress->address->street,
                'postalCode' => $deliveryAddress->address->postalCode,
                'city' => $deliveryAddress->address->city,
                'countryCode' => $deliveryAddress->address->countryCode->value,
            ],
        ], $shipmentId);

        $trackingReference = $response['trackingNumber'] ?? null;

        if (!\is_string($trackingReference) || '' === $trackingReference) {
            throw AcmeClientException::invalidResponse(self::SHIPMENT_PATH, 'A manifest response carries a non-empty "trackingNumber".');
        }

        return $trackingReference;
    }

    /**
     * @throws AcmeClientException
     */
    public function manifestReturn(string $shipmentId, PostalAddress $pickupAddress): string
    {
        $returnAddress = Address::of($this->returnAddressStreet, $this->returnAddressPostalCode, $this->returnAddressCity, $this->returnAddressCountryCode);

        $response = $this->acmeClient->post(self::RETURN_PATH, [
            'clientReferenceId' => $shipmentId,
            'origin' => [
                'recipient' => $pickupAddress->fullName->toString(),
                'street' => $pickupAddress->address->street,
                'postalCode' => $pickupAddress->address->postalCode,
                'city' => $pickupAddress->address->city,
                'countryCode' => $pickupAddress->address->countryCode->value,
            ],
            'destination' => [
                'recipient' => $this->returnAddressRecipient,
                'street' => $returnAddress->street,
                'postalCode' => $returnAddress->postalCode,
                'city' => $returnAddress->city,
                'countryCode' => $returnAddress->countryCode->value,
            ],
        ], $shipmentId);

        $returnTrackingReference = $response['trackingNumber'] ?? null;

        if (!\is_string($returnTrackingReference) || '' === $returnTrackingReference) {
            throw AcmeClientException::invalidResponse(self::RETURN_PATH, 'A return manifest response carries a non-empty "trackingNumber".');
        }

        return $returnTrackingReference;
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
}
