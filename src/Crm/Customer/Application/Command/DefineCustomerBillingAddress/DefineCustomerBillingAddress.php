<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\DefineCustomerBillingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefineCustomerBillingAddress implements CommandInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        public string $customerId,
        public array $billingAddress,
    ) {
    }
}
