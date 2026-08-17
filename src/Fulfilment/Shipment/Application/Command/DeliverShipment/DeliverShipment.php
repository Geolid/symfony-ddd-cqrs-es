<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\DeliverShipment;

use Shared\Application\Command\CommandInterface;

final readonly class DeliverShipment implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
