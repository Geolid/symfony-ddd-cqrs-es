<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Payer;

final readonly class PayerResult
{
    public function __construct(
        public string $payerId,
        public ?PostalAddressResult $address,
    ) {
    }
}
