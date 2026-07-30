<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\ListCustomers;

use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Pagination\PaginationInfo;
use Shared\Application\Query\Result\ListResult;

#[AsQueryHandler]
final readonly class ListCustomersHandler
{
    public function __construct(private CustomerFinderInterface $customerFinder)
    {
    }

    /**
     * @return ListResult<CustomerResult>
     */
    public function __invoke(ListCustomers $query): ListResult
    {
        $paginator = $this->customerFinder->paginate($query->page, $query->itemsPerPage);

        /** @var list<CustomerResult> $items */
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
