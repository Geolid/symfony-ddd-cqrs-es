<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Command\RegisterCustomer;

use Psr\Clock\ClockInterface;
use Sales\Customer\Application\Exception\CustomerEmailAlreadyRegisteredException;
use Sales\Customer\Domain\Customer;
use Sales\Customer\Domain\Exception\CustomerAlreadyExistsException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Sales\Customer\Domain\ValueObject\Email;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[CommandHandler]
final readonly class RegisterCustomerHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CustomerEmailAlreadyRegisteredException
     * @throws CustomerAlreadyExistsException
     */
    public function __invoke(RegisterCustomer $command): void
    {
        $email = Email::fromString($command->email);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), $email->value, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw CustomerEmailAlreadyRegisteredException::forEmail($email->value, $e);
        }

        $customer = Customer::register(
            id: CustomerId::fromString($command->id),
            email: $email,
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($customer);
    }
}
