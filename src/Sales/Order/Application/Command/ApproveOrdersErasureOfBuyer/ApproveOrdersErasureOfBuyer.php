<?php

declare(strict_types=1);

namespace Sales\Order\Application\Command\ApproveOrdersErasureOfBuyer;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveOrdersErasureOfBuyer implements CommandInterface
{
    public function __construct(public string $buyerId)
    {
    }
}
