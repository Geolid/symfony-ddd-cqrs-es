<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\Shipment;

final readonly class PostalAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
