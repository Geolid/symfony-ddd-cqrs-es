<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends PaginatedCollectionFinderInterface
{
    public function byStatus(string ...$values): static;

    public function byTrackingReference(string $trackingReference): static;
}
