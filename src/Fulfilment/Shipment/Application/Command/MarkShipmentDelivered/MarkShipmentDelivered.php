<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\MarkShipmentDelivered;

use Shared\Application\Command\CommandInterface;

final readonly class MarkShipmentDelivered implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
