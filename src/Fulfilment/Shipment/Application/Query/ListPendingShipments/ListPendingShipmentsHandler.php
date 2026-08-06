<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListPendingShipments;

use Fulfilment\Shipment\Application\Enum\AppShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class ListPendingShipmentsHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @return list<ShipmentResult>
     */
    public function __invoke(ListPendingShipments $query): array
    {
        /** @var list<ShipmentResult> */
        return iterator_to_array($this->shipmentFinder->withStatus(AppShipmentStatus::PENDING->value));
    }
}
