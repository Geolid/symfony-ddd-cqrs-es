<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\ApproveShipmentReturn;

use Shared\Application\Command\CommandInterface;

final readonly class ApproveShipmentReturn implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
