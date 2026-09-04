<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\ManifestShipment;

use Shared\Application\Command\CommandInterface;

final readonly class ManifestShipment implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $trackingNumber,
    ) {
    }
}
