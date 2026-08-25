<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\ListOrderSummaries;

use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\ListResult;

#[QueryHandler]
final readonly class ListOrderSummariesHandler
{
    public function __construct(private OrderSummaryFinderInterface $orderSummaryFinder)
    {
    }

    /**
     * @return ListResult<OrderSummaryResult>
     */
    public function __invoke(ListOrderSummaries $query): ListResult
    {
        $finder = $this->orderSummaryFinder;

        if (null !== $query->customerId) {
            $finder = $finder->byCustomer($query->customerId);
        }

        if (null !== $query->status) {
            $finder = $finder->byStatus($query->status);
        }

        if ($query->sortedByPlacedAt) {
            $finder = $finder->sortedByPlacedAt();
        }

        $paginator = $finder->paginate($query->page, $query->itemsPerPage);

        /** @var list<OrderSummaryResult> $items */
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
