<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\DispatchShipmentReturn;

use Shared\Application\Command\CommandInterface;

final readonly class DispatchShipmentReturn implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
