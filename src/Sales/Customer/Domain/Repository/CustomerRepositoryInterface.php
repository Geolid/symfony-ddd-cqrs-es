<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Repository;

use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\ValueObject\CustomerId;

interface CustomerRepositoryInterface
{
    public function has(CustomerId $id): bool;

    /**
     * @throws CustomerNotFoundException
     */
    public function load(CustomerId $id): Customer;

    public function save(Customer $customer): void;
}
