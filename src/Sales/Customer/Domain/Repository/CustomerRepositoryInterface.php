<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\Repository;

use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Shared\Domain\Exception\AggregateNotFoundException;

interface CustomerRepositoryInterface
{
    public function has(CustomerId $id): bool;

    /**
     * @throws AggregateNotFoundException
     */
    public function load(CustomerId $id): Customer;

    public function save(Customer $customer): void;
}
