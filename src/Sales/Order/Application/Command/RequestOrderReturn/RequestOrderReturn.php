<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RequestOrderReturn;

use Shared\Application\Command\CommandInterface;

final readonly class RequestOrderReturn implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $customerId,
    ) {
    }
}
