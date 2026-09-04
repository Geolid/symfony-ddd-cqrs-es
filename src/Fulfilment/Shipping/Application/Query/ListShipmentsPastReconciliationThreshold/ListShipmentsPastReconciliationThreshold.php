<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Query\ListShipmentsPastReconciliationThreshold;

use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<ShipmentResult>>
 */
final readonly class ListShipmentsPastReconciliationThreshold implements QueryInterface
{
}
