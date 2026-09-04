<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\GetShipmentByTrackingNumber;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ShipmentResult>
 */
final readonly class GetShipmentByTrackingNumber implements QueryInterface
{
    public function __construct(public string $trackingNumber)
    {
    }
}
