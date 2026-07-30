<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\DispatchShipment;

use Shared\Application\Command\CommandInterface;

final readonly class DispatchShipment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
