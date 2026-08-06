<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListPendingShipments;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<ShipmentResult>>
 */
final readonly class ListPendingShipments implements QueryInterface
{
}
