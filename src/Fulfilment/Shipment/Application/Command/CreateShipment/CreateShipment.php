<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CreateShipment;

use Shared\Application\Command\CommandInterface;

final readonly class CreateShipment implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
        public string $shippingFirstName,
        public string $shippingLastName,
        public string $shippingStreet,
        public string $shippingPostalCode,
        public string $shippingCity,
    ) {
    }
}
