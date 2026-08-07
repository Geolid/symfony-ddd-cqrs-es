<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\GetCustomerByIdentityId;

use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetCustomerByIdentityIdHandler
{
    public function __construct(private CustomerFinderInterface $customerFinder)
    {
    }

    public function __invoke(GetCustomerByIdentityId $query): ?CustomerResult
    {
        try {
            return $this->customerFinder->ofIdentityId($query->identityId);
        } catch (CustomerResultNotFoundException) {
            return null;
        }
    }
}
