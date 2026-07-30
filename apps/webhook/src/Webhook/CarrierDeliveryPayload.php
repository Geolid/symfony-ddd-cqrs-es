<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use Shipping\Shipment\Application\Validation\ValidShipmentId;

final readonly class CarrierDeliveryPayload
{
    public function __construct(
        #[ValidShipmentId]
        public string $shipmentId,
    ) {
    }
}
