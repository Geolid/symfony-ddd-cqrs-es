<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\GetShipmentByReturnTrackingReference;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ShipmentResult>
 */
final readonly class GetShipmentByReturnTrackingReference implements QueryInterface
{
    public function __construct(public string $returnTrackingReference)
    {
    }
}
