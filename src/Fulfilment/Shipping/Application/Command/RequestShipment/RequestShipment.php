<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Command\RequestShipment;

use Fulfilment\Shipping\Application\ShipmentDirection;
use Shared\Application\Command\CommandInterface;

final readonly class RequestShipment implements CommandInterface
{
    /**
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $origin
     * @param array{recipientName: string, street: string, postalCode: string, city: string, countryCode: string} $destination
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ShipmentDirection $direction,
        public string $buyerId,
        public array $origin,
        public array $destination,
    ) {
    }
}
