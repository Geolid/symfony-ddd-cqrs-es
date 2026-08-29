<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\ListOrderSummaries;

use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Shared\Application\Query\Pagination;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\PaginatedResult;

#[QueryHandler]
final readonly class ListOrderSummariesHandler
{
    public function __construct(private OrderSummaryFinderInterface $orderSummaryFinder)
    {
    }

    /**
     * @return PaginatedResult<OrderSummaryResult>
     */
    public function __invoke(ListOrderSummaries $query): PaginatedResult
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

        return new PaginatedResult($items, Pagination::fromPaginator($paginator));
    }
}
