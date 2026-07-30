<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\ListCustomers;

use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\ListResult;

/**
 * @implements QueryInterface<ListResult<CustomerResult>>
 */
final readonly class ListCustomers implements QueryInterface
{
    public function __construct(
        public int $page = 1,
        public int $itemsPerPage = 20,
    ) {
    }
}
