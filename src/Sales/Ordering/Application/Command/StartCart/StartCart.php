<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\StartCart;

use Shared\Application\Command\CommandInterface;

final readonly class StartCart implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $buyerId,
    ) {
    }
}
