<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Query\GetShipmentByTrackingNumber;

use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentResult;
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
