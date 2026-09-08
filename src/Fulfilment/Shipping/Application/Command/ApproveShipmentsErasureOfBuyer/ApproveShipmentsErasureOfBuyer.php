<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\ApproveShipmentsErasureOfBuyer;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveShipmentsErasureOfBuyer implements CommandInterface
{
    public function __construct(public string $buyerId)
    {
    }
}
