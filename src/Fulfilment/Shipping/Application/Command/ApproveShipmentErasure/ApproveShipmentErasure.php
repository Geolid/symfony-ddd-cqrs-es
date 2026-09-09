<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\ApproveShipmentErasure;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveShipmentErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
