<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\DefineCustomerShippingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefineCustomerShippingAddress implements CommandInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     */
    public function __construct(
        public string $customerId,
        public array $shippingAddress,
    ) {
    }
}
