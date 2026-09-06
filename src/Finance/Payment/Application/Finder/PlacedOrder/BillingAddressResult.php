<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\PlacedOrder;

final readonly class BillingAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
