<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrphanedOrdersOfBuyer;

use Shared\Application\Command\CommandInterface;

final readonly class CancelOrphanedOrdersOfBuyer implements CommandInterface
{
    public function __construct(public string $buyerId)
    {
    }
}
