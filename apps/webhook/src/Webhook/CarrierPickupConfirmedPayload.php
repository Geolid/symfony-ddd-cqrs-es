<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use Fulfilment\Shipping\Application\Validation\ValidTrackingNumber;
use OpenApi\Attributes as OA;

final readonly class CarrierPickupConfirmedPayload
{
    public function __construct(
        #[ValidTrackingNumber]
        #[OA\Property(description: "The carrier's own tracking reference for the picked-up parcel.", example: 'ACME-4Q7X2K9')]
        public string $trackingNumber,
    ) {
    }
}
