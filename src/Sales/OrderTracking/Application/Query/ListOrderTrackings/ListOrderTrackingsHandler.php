<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Query\ListOrderTrackings;

use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingFinderInterface;
use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;

#[AsQueryHandler]
final readonly class ListOrderTrackingsHandler
{
    public function __construct(private OrderTrackingFinderInterface $orderTrackingFinder)
    {
    }

    /**
     * @return ListResult<OrderTrackingResult>
     */
    public function __invoke(ListOrderTrackings $query): ListResult
    {
        $finder = $this->orderTrackingFinder;

        if (null !== $query->customerId) {
            $finder = $finder->withCustomer($query->customerId);
        }

        if (null !== $query->status) {
            $finder = $finder->withStatus($query->status);
        }

        $paginator = $finder->paginate($query->page, $query->itemsPerPage);

        /** @var list<OrderTrackingResult> $items */
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
