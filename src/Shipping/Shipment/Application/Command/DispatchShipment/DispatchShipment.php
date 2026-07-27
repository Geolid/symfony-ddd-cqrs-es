<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Command\DispatchShipment;

use Shared\Application\Command\CommandInterface;

final readonly class DispatchShipment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
