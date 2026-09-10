<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Shopper;

final readonly class ShopperResult
{
    public function __construct(
        public string $shopperId,
        public ?PostalAddressResult $shippingAddress,
        public ?PostalAddressResult $billingAddress,
        public bool $erasureRequested,
    ) {
    }
}
