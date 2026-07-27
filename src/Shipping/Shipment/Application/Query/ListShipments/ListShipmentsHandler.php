<?php

declare(strict_types=1);

namespace Shipping\Shipment\Application\Query\ListShipments;

use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;

#[AsQueryHandler]
final readonly class ListShipmentsHandler
{
    public function __construct(private ShipmentFinderInterface $shipmentFinder)
    {
    }

    /**
     * @return ListResult<ShipmentResult>
     */
    public function __invoke(ListShipments $query): ListResult
    {
        $finder = null !== $query->status ? $this->shipmentFinder->withStatus($query->status) : $this->shipmentFinder;
        $paginator = $finder->paginate($query->page, $query->itemsPerPage);

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
