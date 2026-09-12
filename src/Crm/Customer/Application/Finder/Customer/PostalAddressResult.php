<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Finder\Customer;

final readonly class PostalAddressResult
{
    public function __construct(
        public string $recipientName,
        public AddressResult $address,
    ) {
    }
}
