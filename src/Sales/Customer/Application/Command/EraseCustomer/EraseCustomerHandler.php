<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\EraseCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\CustomerId;
use Sales\Customer\Domain\CustomerUniqueValue;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class EraseCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(EraseCustomer $command): void
    {
        $customer = $this->repository->load(CustomerId::fromString($command->id));

        // Read before recording — once the cipher key is dropped this fingerprint is unrecoverable.
        $fingerprint = $customer->email()->fingerprint();

        $customer->erase($this->clock->now());

        $this->repository->save($customer);
        $this->uniqueValues->release(CustomerUniqueValue::EMAIL, $fingerprint);
    }
}
