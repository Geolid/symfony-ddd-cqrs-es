<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\PaymentCapture;

final readonly class PaymentCaptureResult
{
    public function __construct(
        public string $orderId,
        public bool $captured,
    ) {
    }
}
