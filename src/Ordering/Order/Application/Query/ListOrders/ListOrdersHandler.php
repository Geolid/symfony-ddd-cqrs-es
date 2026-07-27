<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Query\ListOrders;

use Ordering\Order\Application\Finder\Order\OrderFinderInterface;
use Ordering\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;

#[AsQueryHandler]
final readonly class ListOrdersHandler
{
    public function __construct(private OrderFinderInterface $orderFinder)
    {
    }

    /**
     * @return ListResult<OrderResult>
     */
    public function __invoke(ListOrders $query): ListResult
    {
        $paginator = $this->orderFinder->paginate($query->page, $query->itemsPerPage);

        /** @var list<OrderResult> $items */
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
