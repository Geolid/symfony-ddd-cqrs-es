<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Carrier\Acme;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\Exception\AcmeClientException;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AcmeCarrierGateway implements CarrierGatewayInterface
{
    private const string PICKUP_PATH = '/pickups';
    private const string RETURN_PICKUP_PATH = '/returns';
    private const string STATUS_PATH = '/status';

    public function __construct(
        private AcmeClient $acmeClient,
        #[Autowire(param: 'fulfilment.return_address_name')]
        private string $returnAddressName,
        #[Autowire(param: 'fulfilment.return_address_street')]
        private string $returnAddressStreet,
        #[Autowire(param: 'fulfilment.return_address_postal_code')]
        private string $returnAddressPostalCode,
        #[Autowire(param: 'fulfilment.return_address_city')]
        private string $returnAddressCity,
    ) {
    }

    /**
     * @throws AcmeClientException
     */
    public function requestPickup(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        $response = $this->acmeClient->post(self::PICKUP_PATH, [
            'clientReferenceId' => $shipmentId,
            'destination' => [
                'recipient' => $deliveryAddress->fullName->toString(),
                'street' => $deliveryAddress->address->street,
                'postalCode' => $deliveryAddress->address->postalCode,
                'city' => $deliveryAddress->address->city,
            ],
        ], $shipmentId);

        $trackingReference = $response['trackingNumber'] ?? null;

        if (!\is_string($trackingReference) || '' === $trackingReference) {
            throw AcmeClientException::invalidResponse(self::PICKUP_PATH, 'A pickup response carries a non-empty "trackingNumber".');
        }

        return $trackingReference;
    }

    /**
     * @throws AcmeClientException
     */
    public function requestReturnPickup(string $shipmentId, PostalAddress $pickupAddress): string
    {
        $returnAddress = Address::of($this->returnAddressStreet, $this->returnAddressPostalCode, $this->returnAddressCity);

        $response = $this->acmeClient->post(self::RETURN_PICKUP_PATH, [
            'clientReferenceId' => $shipmentId,
            'origin' => [
                'recipient' => $pickupAddress->fullName->toString(),
                'street' => $pickupAddress->address->street,
                'postalCode' => $pickupAddress->address->postalCode,
                'city' => $pickupAddress->address->city,
            ],
            'destination' => [
                'recipient' => $this->returnAddressName,
                'street' => $returnAddress->street,
                'postalCode' => $returnAddress->postalCode,
                'city' => $returnAddress->city,
            ],
        ], $shipmentId);

        $returnTrackingReference = $response['trackingNumber'] ?? null;

        if (!\is_string($returnTrackingReference) || '' === $returnTrackingReference) {
            throw AcmeClientException::invalidResponse(self::RETURN_PICKUP_PATH, 'A return pickup response carries a non-empty "trackingNumber".');
        }

        return $returnTrackingReference;
    }

    /**
     * @throws AcmeClientException
     */
    public function checkStatus(string $reference): string
    {
        $response = $this->acmeClient->get(self::STATUS_PATH.'/'.$reference);

        $status = $response['status'] ?? null;

        if (!\is_string($status) || '' === $status) {
            throw AcmeClientException::invalidResponse(self::STATUS_PATH, 'A status response carries a non-empty "status".');
        }

        return $status;
    }
}
