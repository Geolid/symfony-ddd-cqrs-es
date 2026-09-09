<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\DefineBuyerBillingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefineBuyerBillingAddress implements CommandInterface
{
    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $billingAddress
     */
    public function __construct(
        public string $buyerId,
        public array $billingAddress,
    ) {
    }
}
