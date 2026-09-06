<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

final readonly class PostalAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
