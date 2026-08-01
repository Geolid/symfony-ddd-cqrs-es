<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Infrastructure\Gateway\Acme;

use Fulfilment\Shipment\Application\Gateway\CarrierGatewayInterface;
use Shared\Infrastructure\Gateway\Acme\AcmeClient;
use Shared\Infrastructure\Gateway\Acme\Exception\AcmeClientException;

final readonly class AcmeCarrierGateway implements CarrierGatewayInterface
{
    private const string PICKUP_PATH = '/pickups';

    public function __construct(private AcmeClient $acmeClient)
    {
    }

    /**
     * @throws AcmeClientException
     */
    public function requestPickup(string $shipmentId, string $deliveryAddress): string
    {
        $response = $this->acmeClient->post(self::PICKUP_PATH, [
            'reference' => $shipmentId,
            'destination' => $deliveryAddress,
        ]);

        $trackingReference = $response['trackingNumber'] ?? null;

        if (!\is_string($trackingReference) || '' === $trackingReference) {
            throw AcmeClientException::invalidResponse(self::PICKUP_PATH, 'A pickup response carries a non-empty "trackingNumber".');
        }

        return $trackingReference;
    }
}
