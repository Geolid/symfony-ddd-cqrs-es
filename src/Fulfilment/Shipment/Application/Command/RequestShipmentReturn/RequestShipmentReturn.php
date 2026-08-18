<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\RequestShipmentReturn;

use Shared\Application\Command\CommandInterface;

final readonly class RequestShipmentReturn implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
