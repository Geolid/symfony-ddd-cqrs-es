<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\DeliveredOrder;

final readonly class ShippingAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
