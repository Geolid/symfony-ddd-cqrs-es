<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Customer;

use Shared\Application\ErasureStatus;

final readonly class CustomerResult
{
    public function __construct(
        public string $id,
        public ?PostalAddressResult $shippingAddress,
        public ?PostalAddressResult $billingAddress,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
