<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RegisterShopper;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterShopper implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $email,
    ) {
    }
}
