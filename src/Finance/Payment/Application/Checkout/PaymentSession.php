<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

final readonly class PaymentSession
{
    public function __construct(
        public string $reference,
        public string $checkoutUrl,
    ) {
    }
}
