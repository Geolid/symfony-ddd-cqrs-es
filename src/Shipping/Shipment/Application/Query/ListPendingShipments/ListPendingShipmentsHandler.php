<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Query\ListPendingShipments;

use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shipping\Shipment\Domain\ShipmentStatus;

#[AsQueryHandler]
final readonly class ListPendingShipmentsHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @return ListResult<ShipmentResult>
     */
    public function __invoke(ListPendingShipments $query): ListResult
    {
        $paginator = $this->shipmentFinder
            ->withStatus(ShipmentStatus::PENDING->value)
            ->paginate($query->page, $query->itemsPerPage);

        /** @var list<ShipmentResult> $items */
        $items = iterator_to_array($paginator);

        return new ListResult(
            $items,
            new PaginationInfo(
                totalItems: $paginator->totalItems(),
                currentPage: $paginator->currentPage(),
                itemsPerPage: $paginator->itemsPerPage(),
                lastPage: $paginator->lastPage(),
            ),
        );
    }
}
