<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueValue;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Email;

#[AsCommandHandler]
final readonly class RegisterCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AddressAlreadyRegisteredException
     */
    public function __invoke(RegisterCustomer $command): void
    {
        $email = Email::fromString($command->email);
        $fingerprint = $email->fingerprint();

        try {
            $this->uniqueValues->reserve(CustomerUniqueValue::EMAIL, $fingerprint);
        } catch (UniqueValueAlreadyTakenException) {
            throw AddressAlreadyRegisteredException::forFingerprint($fingerprint);
        }

        $customer = Customer::register(
            id: CustomerId::fromString($command->id),
            email: $email,
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($customer);
    }
}
