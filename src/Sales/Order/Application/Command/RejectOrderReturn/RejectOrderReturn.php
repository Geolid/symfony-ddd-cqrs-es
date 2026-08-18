<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\RejectOrderReturn;

use Shared\Application\Command\CommandInterface;

final readonly class RejectOrderReturn implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $reason,
    ) {
    }
}
