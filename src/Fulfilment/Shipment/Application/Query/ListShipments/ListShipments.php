<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListShipments;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;

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
