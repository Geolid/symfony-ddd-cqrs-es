<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Query\ListShipments;

use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;

/**
 * @implements QueryInterface<ListResult<ShipmentResult>>
 */
final readonly class ListShipments implements QueryInterface
{
    public function __construct(
        public ?string $status = null,
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
