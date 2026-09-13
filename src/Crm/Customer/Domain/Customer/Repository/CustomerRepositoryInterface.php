<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\Repository;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;

interface CustomerRepositoryInterface
{
    public function has(CustomerId $id): bool;

    /**
     * @throws CustomerNotFoundException
     */
    public function load(CustomerId $id): Customer;

    /**
     * @throws CustomerAlreadyExistsException
     */
    public function save(Customer $customer): void;
}
