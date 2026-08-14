<?php

declare(strict_types=1);

namespace Sales\Customer\Infrastructure\Persistence\EventStore\Repository;

use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Sales\Customer\Domain\CustomerAddresses;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CustomerAddressesRepository implements CustomerAddressesRepositoryInterface
{
    /**
     * @param Repository<CustomerAddresses> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.sales.customer.addresses.repository')]
        private Repository $repository,
    ) {
    }

    public function has(CustomerId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(CustomerId $id): CustomerAddresses
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw CustomerNotFoundException::forId($id);
        }
    }

    public function save(CustomerAddresses $customerAddresses): void
    {
        $this->repository->save($customerAddresses);
    }
}
