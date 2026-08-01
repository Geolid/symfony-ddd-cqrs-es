<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterCustomer implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $email,
    ) {
    }
}
