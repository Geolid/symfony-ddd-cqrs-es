<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\GetShipmentByReturnTrackingReference;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetShipmentByReturnTrackingReferenceHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @throws ShipmentResultNotFoundException
     */
    public function __invoke(GetShipmentByReturnTrackingReference $query): ShipmentResult
    {
        return $this->shipmentFinder->ofReturnTrackingReference($query->returnTrackingReference);
    }
}
