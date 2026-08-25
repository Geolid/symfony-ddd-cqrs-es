<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\GetShipmentByTrackingReference;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\QueryUseCase;

#[QueryUseCase]
final readonly class GetShipmentByTrackingReferenceHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @throws ShipmentResultNotFoundException
     */
    public function __invoke(GetShipmentByTrackingReference $query): ShipmentResult
    {
        return $this->shipmentFinder->ofTrackingReference($query->trackingReference);
    }
}
