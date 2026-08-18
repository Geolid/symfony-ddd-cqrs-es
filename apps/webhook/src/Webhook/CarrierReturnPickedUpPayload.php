<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use Fulfilment\Shipment\Application\Validation\ValidTrackingReference;
use OpenApi\Attributes as OA;

final readonly class CarrierReturnPickedUpPayload
{
    public function __construct(
        #[ValidTrackingReference]
        #[OA\Property(description: "The carrier's own tracking reference for the return leg.", example: 'ACME-RETURN-4Q7X2K9')]
        public string $returnTrackingReference,
    ) {
    }
}
