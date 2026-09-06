<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Finder\PlacedOrder;

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
