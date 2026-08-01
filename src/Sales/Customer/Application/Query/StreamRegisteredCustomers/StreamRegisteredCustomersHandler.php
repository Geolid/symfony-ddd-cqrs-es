<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\StreamRegisteredCustomers;

use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class StreamRegisteredCustomersHandler
{
    public function __construct(private CustomerFinderInterface $customerFinder)
    {
    }

    /**
     * @return StreamResult<CustomerResult>
     */
    public function __invoke(StreamRegisteredCustomers $query): StreamResult
    {
        return new StreamResult($this->customerFinder->withoutErased());
    }
}
