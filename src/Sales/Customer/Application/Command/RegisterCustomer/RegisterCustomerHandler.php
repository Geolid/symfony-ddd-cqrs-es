<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\CustomerId;
use Sales\Customer\Domain\CustomerUniqueValue;
use Sales\Customer\Domain\Email;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class RegisterCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RegisterCustomer $command): void
    {
        $email = Email::fromString($command->email);

        $this->uniqueValues->reserve(CustomerUniqueValue::EMAIL, $email->fingerprint());

        $this->repository->save(Customer::register(
            CustomerId::fromString($command->id),
            $email,
            $this->clock->now(),
        ));
    }
}
