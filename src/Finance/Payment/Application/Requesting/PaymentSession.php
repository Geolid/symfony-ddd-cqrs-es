<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

final readonly class PaymentSession
{
    public function __construct(
        public string $reference,
        public string $checkoutUrl,
    ) {
    }
}
