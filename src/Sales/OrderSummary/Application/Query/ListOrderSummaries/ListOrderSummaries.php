<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\ListOrderSummaries;

use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\PaginatedResult;

/**
 * @implements QueryInterface<PaginatedResult<OrderSummaryResult>>
 */
final readonly class ListOrderSummaries implements QueryInterface
{
    public function __construct(
        public ?string $customerId = null,
        public ?OrderSummaryStatus $status = null,
        public int $page = 1,
        public int $itemsPerPage = 20,
        public bool $sortedByPlacedAt = false,
    ) {
    }
}
