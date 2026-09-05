<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\PlacedPayment;

final readonly class PlacedPaymentResult
{
    public function __construct(
        public string $orderId,
        public string $paymentId,
        public int $amountInCents,
    ) {
    }
}
