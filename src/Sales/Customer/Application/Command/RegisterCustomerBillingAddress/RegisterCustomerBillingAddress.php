<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomerBillingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterCustomerBillingAddress implements CommandInterface
{
    public function __construct(
        public string $customerId,
        public string $recipientName,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
    }
}
