<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\EraseOrderBillingAddress;

use Shared\Application\Command\CommandInterface;

final readonly class EraseOrderBillingAddress implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
