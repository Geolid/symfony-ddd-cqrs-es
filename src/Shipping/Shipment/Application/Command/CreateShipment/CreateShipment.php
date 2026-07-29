<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Command\CreateShipment;

use Shared\Application\Command\CommandInterface;

final readonly class CreateShipment implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
    ) {
    }
}
