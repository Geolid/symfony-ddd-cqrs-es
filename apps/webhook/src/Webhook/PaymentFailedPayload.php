<?php

declare(strict_types=1);

namespace Webhook\Webhook;

use OpenApi\Attributes as OA;
use Sales\Order\Application\Validation\ValidPaymentReference;

final readonly class PaymentFailedPayload
{
    public function __construct(
        #[ValidPaymentReference]
        #[OA\Property(description: "The payment provider's own reference for the failed charge.", example: 'GLBX-9F3K2M1P')]
        public string $paymentReference,
    ) {
    }
}
