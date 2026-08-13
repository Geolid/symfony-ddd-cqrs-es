<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

final readonly class PaymentSession
{
    public function __construct(
        public string $reference,
        public string $checkoutUrl,
    ) {
    }
}
