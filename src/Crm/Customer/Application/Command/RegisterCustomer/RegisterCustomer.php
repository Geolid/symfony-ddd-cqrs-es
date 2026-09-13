<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Command\RegisterCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterCustomer implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {
    }
}
