<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\CreateShipment;

use Shared\Application\Command\CommandInterface;

final readonly class CreateShipment implements CommandInterface
{
    /**
     * @param array{firstName: string, lastName: string, street: string, postalCode: string, city: string} $shippingAddress
     */
    public function __construct(
        public string $id,
        public string $orderId,
        public string $customerId,
        public array $shippingAddress,
    ) {
    }
}
