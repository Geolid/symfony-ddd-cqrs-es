<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\EraseCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Exception\CustomerAlreadyExistsException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class EraseCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerNotFoundException
     * @throws CustomerAlreadyExistsException
     */
    public function __invoke(EraseCustomer $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));
        $customer->erase($this->clock->now());

        $this->repository->save($customer);

        $this->uniqueValues->releaseAll(UniqueKey::for(CustomerUniqueKey::EMAIL), $customer->id->toString());
    }
}
