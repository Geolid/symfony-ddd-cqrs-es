<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Shopper;

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
