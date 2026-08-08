<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListPendingShipments;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class ListPendingShipmentsHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @return StreamResult<ShipmentResult>
     */
    public function __invoke(ListPendingShipments $query): StreamResult
    {
        return new StreamResult($this->shipmentFinder->withStatus(ShipmentStatus::PENDING->value));
    }
}
