<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Exception\CustomerAlreadyExistsException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelCustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @param Repository<Customer> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.customer.customer.repository')]
        private Repository $repository,
    ) {
    }

    public function has(CustomerId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(CustomerId $id): Customer
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw CustomerNotFoundException::forId($id->toString());
        }
    }

    public function save(Customer $customer): void
    {
        try {
            $this->repository->save($customer);
        } catch (AggregateAlreadyExists) {
            throw CustomerAlreadyExistsException::forId($customer->id->toString());
        }
    }
}
