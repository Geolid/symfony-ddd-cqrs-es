<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\EventStore\Repository;

use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CustomerRepository implements CustomerRepositoryInterface
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
        $this->repository->save($customer);
    }
}
