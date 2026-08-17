<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListRequestedShipments;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<ShipmentResult>>
 */
final readonly class ListRequestedShipments implements QueryInterface
{
}
