<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Command\ManifestShipmentReturn;

use Shared\Application\Command\CommandInterface;

final readonly class ManifestShipmentReturn implements CommandInterface
{
    public function __construct(
        public string $id,
        public string $returnTrackingReference,
    ) {
    }
}
