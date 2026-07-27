<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Finder\Shipment;

use Shared\Application\Finder\PaginatedFinderInterface;

/**
 * @extends PaginatedFinderInterface<ShipmentResult>
 */
interface ShipmentFinderInterface extends PaginatedFinderInterface
{
    public function withStatus(string $status): static;
}
