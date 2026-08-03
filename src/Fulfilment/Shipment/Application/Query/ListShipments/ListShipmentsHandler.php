<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Query\ListShipments;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Domain\ShipmentStatus;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;

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
        $finder = null !== $query->status ? $this->shipmentFinder->withStatus(ShipmentStatus::from($query->status)) : $this->shipmentFinder;
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
