<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Buyer;

final readonly class BuyerResult
{
    public function __construct(
        public string $buyerId,
        public ?PostalAddressResult $shippingAddress,
        public ?PostalAddressResult $billingAddress,
        public bool $erasureRequested,
    ) {
    }
}
