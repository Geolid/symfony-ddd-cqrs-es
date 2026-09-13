<?php

declare(strict_types=1);

namespace Crm\Customer\Infrastructure\EventStore;

use Crm\Customer\Domain\Customer\Customer;
use Crm\Customer\Domain\Customer\Exception\CustomerAlreadyExistsException;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Customer\Domain\Customer\Repository\CustomerRepositoryInterface;
use Crm\Customer\Domain\Customer\ValueObject\CustomerId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelCustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @param Repository<Customer> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.crm.customer.customer.repository')]
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
