<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Carrier\Acme;

use Fulfilment\Shipment\Application\Carrier\CarrierGatewayInterface;
use Fulfilment\Shipment\Infrastructure\Carrier\Acme\Exception\AcmeClientException;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class AcmeCarrierGateway implements CarrierGatewayInterface
{
    private const string PICKUP_PATH = '/pickups';

    public function __construct(private AcmeClient $acmeClient)
    {
    }

    /**
     * @throws AcmeClientException
     */
    public function requestPickup(string $shipmentId, PostalAddress $deliveryAddress): string
    {
        $response = $this->acmeClient->post(self::PICKUP_PATH, [
            'reference' => $shipmentId,
            'destination' => \sprintf(
                '%s, %s, %s %s',
                $deliveryAddress->fullName->toString(),
                $deliveryAddress->address->street,
                $deliveryAddress->address->postalCode,
                $deliveryAddress->address->city,
            ),
        ]);

        $trackingReference = $response['trackingNumber'] ?? null;

        if (!\is_string($trackingReference) || '' === $trackingReference) {
            throw AcmeClientException::invalidResponse(self::PICKUP_PATH, 'A pickup response carries a non-empty "trackingNumber".');
        }

        return $trackingReference;
    }
}
