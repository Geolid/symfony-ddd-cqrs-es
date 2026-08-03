<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Finder\Shipment;

use Fulfilment\Shipment\Domain\ShipmentStatus;
use Shared\Application\Finder\PaginatedFinderInterface;

/**
 * @extends PaginatedFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends PaginatedFinderInterface
{
    public function withStatus(ShipmentStatus $status): static;

    public function withTrackingReference(string $trackingReference): static;

    public function ofOrder(string $orderId): ?ShipmentResult;
}
