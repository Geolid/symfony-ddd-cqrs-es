<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\DefineBuyerShippingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefineBuyerShippingAddress implements CommandInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $shippingAddress
     */
    public function __construct(
        public string $buyerId,
        public array $shippingAddress,
    ) {
    }
}
