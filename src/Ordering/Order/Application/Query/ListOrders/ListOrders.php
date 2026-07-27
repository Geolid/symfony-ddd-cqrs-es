<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Query\ListOrders;

use Ordering\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;

/**
 * @implements QueryInterface<ListResult<OrderResult>>
 */
final readonly class ListOrders implements QueryInterface
{
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
