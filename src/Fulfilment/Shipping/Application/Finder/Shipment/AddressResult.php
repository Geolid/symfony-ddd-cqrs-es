<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\Shipment;

final readonly class AddressResult
{
    public function __construct(
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
    }
}
