<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Query\GetShipmentByTrackingNumber;

use Fulfilment\Shipping\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetShipmentByTrackingNumberHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @throws ShipmentResultNotFoundException
     */
    public function __invoke(GetShipmentByTrackingNumber $query): ShipmentResult
    {
        return $this->shipmentFinder->ofTrackingNumber($query->trackingNumber);
    }
}
