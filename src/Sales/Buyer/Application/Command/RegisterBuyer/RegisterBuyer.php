<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\RegisterBuyer;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterBuyer implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $email,
    ) {
    }
}
