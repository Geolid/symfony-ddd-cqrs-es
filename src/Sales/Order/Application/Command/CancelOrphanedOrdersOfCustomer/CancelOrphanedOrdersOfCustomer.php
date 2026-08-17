<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrphanedOrdersOfCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class CancelOrphanedOrdersOfCustomer implements CommandInterface
{
    public function __construct(public string $customerId)
    {
    }
}
