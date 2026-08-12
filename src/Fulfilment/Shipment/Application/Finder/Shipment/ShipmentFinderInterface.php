<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws ShipmentResultNotFoundException
     */
    public function ofTrackingReference(string $trackingReference): ShipmentResult;

    public function byStatus(string $status): static;
}
