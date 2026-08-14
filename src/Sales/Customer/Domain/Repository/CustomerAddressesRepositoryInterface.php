<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Repository;

use Sales\Customer\Domain\CustomerAddresses;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\ValueObject\CustomerId;

interface CustomerAddressesRepositoryInterface
{
    /**
     * @throws CustomerNotFoundException
     */
    public function load(CustomerId $id): CustomerAddresses;

    public function save(CustomerAddresses $customerAddresses): void;
}
