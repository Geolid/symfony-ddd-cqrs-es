<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\SetCustomerShippingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class SetCustomerShippingAddress implements CommandInterface
{
    public function __construct(
        public string $customerId,
        public string $firstName,
        public string $lastName,
        public string $street,
        public string $postalCode,
        public string $city,
    ) {
    }
}
