<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListRequestedShipments;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class ListRequestedShipmentsHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @return StreamResult<ShipmentResult>
     */
    public function __invoke(ListRequestedShipments $query): StreamResult
    {
        return new StreamResult($this->shipmentFinder->byStatus(ShipmentStatus::REQUESTED->value));
    }
}
