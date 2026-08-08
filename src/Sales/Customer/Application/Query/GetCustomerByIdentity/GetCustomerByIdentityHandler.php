<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\GetCustomerByIdentity;

use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetCustomerByIdentityHandler
{
    public function __construct(private CustomerFinderInterface $customerFinder)
    {
    }

    /**
     * @throws CustomerResultNotFoundException
     */
    public function __invoke(GetCustomerByIdentity $query): CustomerResult
    {
        return $this->customerFinder->ofId($query->identityId);
    }
}
