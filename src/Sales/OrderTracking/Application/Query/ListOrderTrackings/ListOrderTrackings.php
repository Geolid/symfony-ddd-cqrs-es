<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Query\ListOrderTrackings;

use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;

/**
 * @implements QueryInterface<ListResult<OrderTrackingResult>>
 */
final readonly class ListOrderTrackings implements QueryInterface
{
    public function __construct(
        public ?string $customerId = null,
        public ?string $status = null,
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
