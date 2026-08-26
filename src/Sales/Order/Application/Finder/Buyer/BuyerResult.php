<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Buyer;

final readonly class BuyerResult
{
    public function __construct(
        public string $customerId,
        public ?PostalAddressResult $shippingAddress,
        public ?PostalAddressResult $billingAddress,
    ) {
    }
}
