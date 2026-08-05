<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends PaginatedCollectionFinderInterface
{
    public function withStatus(string $status): static;

    public function withTrackingReference(string $trackingReference): static;
}
