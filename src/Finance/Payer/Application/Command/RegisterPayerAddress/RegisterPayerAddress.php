<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\RegisterPayerAddress;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterPayerAddress implements CommandInterface
{
    public function __construct(
        public string $payerId,
        public string $recipientName,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
    }
}
