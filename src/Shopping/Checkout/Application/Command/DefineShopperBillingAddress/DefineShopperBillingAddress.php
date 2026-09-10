<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\DefineShopperBillingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefineShopperBillingAddress implements CommandInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        public string $shopperId,
        public array $billingAddress,
    ) {
    }
}
