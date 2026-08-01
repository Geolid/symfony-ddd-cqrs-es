<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CarrierDeliveryPayload
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        #[OA\Property(description: "The carrier's own tracking reference for the delivered parcel.", example: 'ACME-4Q7X2K9')]
        public string $trackingReference,
    ) {
    }
}
