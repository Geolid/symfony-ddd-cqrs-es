<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\RejectShipmentReturn;

use Shared\Application\Command\CommandInterface;

final readonly class RejectShipmentReturn implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $reason,
    ) {
    }
}
