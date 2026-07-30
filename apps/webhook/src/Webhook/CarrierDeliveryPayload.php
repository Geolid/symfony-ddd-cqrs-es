<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use Fulfilment\Shipment\Application\Validation\ValidShipmentId;
use OpenApi\Attributes as OA;

final readonly class CarrierDeliveryPayload
{
    public function __construct(
        #[ValidShipmentId]
        #[OA\Property(description: 'Identifier of the shipment the carrier has delivered.', example: '0195f2c4-8f7a-7c3e-9b1d-2a4c6e8f0a12')]
        public string $shipmentId,
    ) {
    }
}
