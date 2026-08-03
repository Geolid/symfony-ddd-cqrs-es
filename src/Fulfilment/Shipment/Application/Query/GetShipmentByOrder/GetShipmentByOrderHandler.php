<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\GetShipmentByOrder;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetShipmentByOrderHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    public function __invoke(GetShipmentByOrder $query): ?ShipmentResult
    {
        return $this->shipmentFinder->ofOrder($query->orderId);
    }
}
