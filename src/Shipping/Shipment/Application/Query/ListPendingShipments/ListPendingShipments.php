<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Query\ListPendingShipments;

use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;

/**
 * @implements QueryInterface<ListResult<ShipmentResult>>
 */
final readonly class ListPendingShipments implements QueryInterface
{
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
