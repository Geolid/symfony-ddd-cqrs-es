<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomerShippingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterCustomerShippingAddress implements CommandInterface
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
