<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\RegisterBuyerBillingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterBuyerBillingAddress implements CommandInterface
{
    public function __construct(
        public string $buyerId,
        public string $recipientName,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
    }
}
