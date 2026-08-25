<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\EraseCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Shared\Application\Command\CommandUseCase;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[CommandUseCase]
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
     */
    public function __invoke(EraseCustomer $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));
        $customer->erase($this->clock->now());

        $this->repository->save($customer);

        $this->uniqueValues->release(UniqueKey::for(CustomerUniqueKey::EMAIL), $customer->email->value, $customer->id->toString());
    }
}
