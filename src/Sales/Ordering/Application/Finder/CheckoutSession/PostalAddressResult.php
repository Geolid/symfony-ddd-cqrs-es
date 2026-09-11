<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CheckoutSession;

final readonly class PostalAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
