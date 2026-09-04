<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\RegisterBuyer;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterBuyer implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $email,
    ) {
    }
}
