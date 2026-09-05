<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Finder\RequestedPayment;

final readonly class RequestedPaymentResult
{
    public function __construct(
        public string $orderId,
        public string $paymentId,
        public int $amountInCents,
    ) {
    }
}
