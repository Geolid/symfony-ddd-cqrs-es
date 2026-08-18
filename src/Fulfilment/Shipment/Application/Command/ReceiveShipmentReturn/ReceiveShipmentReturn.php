<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\ReceiveShipmentReturn;

use Shared\Application\Command\CommandInterface;

final readonly class ReceiveShipmentReturn implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
