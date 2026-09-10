<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\DefineShopperShippingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefineShopperShippingAddress implements CommandInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     */
    public function __construct(
        public string $shopperId,
        public array $shippingAddress,
    ) {
    }
}
