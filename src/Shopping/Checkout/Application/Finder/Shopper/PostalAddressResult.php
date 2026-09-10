<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Shopper;

final readonly class PostalAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
