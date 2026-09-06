<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\DefinePayerPostalAddress;

use Shared\Application\Command\CommandInterface;

final readonly class DefinePayerPostalAddress implements CommandInterface
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
