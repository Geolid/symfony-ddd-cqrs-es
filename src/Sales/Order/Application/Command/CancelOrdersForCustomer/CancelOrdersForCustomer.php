<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\CancelOrdersForCustomer;

use Shared\Application\Command\CommandInterface;

final readonly class CancelOrdersForCustomer implements CommandInterface
{
    public function __construct(public string $customerId)
    {
    }
}
