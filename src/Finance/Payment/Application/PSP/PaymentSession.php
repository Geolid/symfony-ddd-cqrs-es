<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP;

final readonly class PaymentSession
{
    public function __construct(
        public string $reference,
        public string $hostedPageUrl,
    ) {
    }
}
