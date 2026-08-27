<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

final readonly class PostalAddressResult
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
    }
}
