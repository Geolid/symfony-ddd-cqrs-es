<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\GetShipmentByTrackingReference;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ShipmentResult>
 */
final readonly class GetShipmentByTrackingReference implements QueryInterface
{
    public function __construct(public string $trackingReference)
    {
    }
}
